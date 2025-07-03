<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

// Add content element to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'mod {
        wizards.newContentElement.wizardItems.special {
            elements {
                accessibility_content {
                    iconIdentifier = content-special-html
                    title = Barrier Free Content
                    description = Adds a barrier free content element
                    tt_content_defValues {
                        CType = accessibility_barrier_free_content
                    }
                }
            }
            show := addToList(accessibility_content)
        }
    }'
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1751463991] = [
    'nodeName' => 'accessibility_helptext',
    'priority' => 40,
    'class' => \FourViewture\Accessibility\Form\Element\HelpTextElement::class,
];
