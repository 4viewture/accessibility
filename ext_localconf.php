<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

// Add content element to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'mod {
        wizards.newContentElement.wizardItems.special {
            elements {
                accessibility_barrier_free_content {
                    iconIdentifier = tx-accessibility-barrier-free-content
                    title = LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free.title
                    description = LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free.description
                    tt_content_defValues {
                        CType = accessibility_barrier_free_content
                    }
                }
            }
            show := addToList(accessibility_barrier_free_content)
        }
    }'
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1751463991] = [
    'nodeName' => 'accessibility_helptext',
    'priority' => 40,
    'class' => \FourViewture\Accessibility\Form\Element\HelpTextElement::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746747770] = [
    'nodeName' => 'accessibilityUpdateTextViaAiControl',
    'priority' => 30,
    'class' => \FourViewture\Accessibility\FormEngine\FieldControl\UpdateTextViaAiControl::class
];
