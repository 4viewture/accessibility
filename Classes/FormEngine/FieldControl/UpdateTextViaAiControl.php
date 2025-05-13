<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\FormEngine\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

class UpdateTextViaAiControl extends AbstractNode
{
    public function render()
    {
        $result = [
            'iconIdentifier' => 'actions-accessibility',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:UpdateTextViaAiControl.title'),
            'linkAttributes' => [
                'class' => 'UpdateTextViaAi ',
                'data-uid' => $this->data['vanillaUid'],
                'data-update-text-via-ai-handler' => 'handler',
                'data-table' => $this->data['tableName'],
                'data-field' => $this->data['fieldName'],
            ],
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@4viewture/accessibility/UpdateTextViaAiControl.js')
            ]
        ];
        return $result;
    }
}
