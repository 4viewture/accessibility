<?php

namespace FourViewture\Accessibility\Form\Element;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class HelpTextElement extends AbstractFormElement
{
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $this->renderView(
            $this->data['parameterArray']['fieldConf']['config']['description'] ?? ''
        );

        return $resultArray;
    }

    protected function renderView(string $description)
    {
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setFormat('html');
        $view->setPartialRootPaths(
            [
                'EXT:accessibility/Resources/Private/Partials/'
            ]
        );
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:accessibility/Resources/Private/Templates/FormElements/HelpTextElement/Standard.html'
            )
        );

        $view->assignMultiple(
            [
                'description' => $description
            ]
        );
        return $view->render();
    }
}
