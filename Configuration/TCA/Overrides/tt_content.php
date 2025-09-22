<?php

defined('TYPO3') or die();

// Register static TypoScript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'accessibility',
    'Configuration/TypoScript',
    'Accessibility Barrier Free Content'
);

// Add the content element to the "special" section of the new content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free.title', // The plugin title
        'accessibility_barrier_free_content', // The plugin key
        'tx-accessibility-barrier-free-content' // Icon identifier
    ],
    'CType', // The field to add it to
    'accessibility' // The extension key
);

// Configure the default backend fields for the content element
$GLOBALS['TCA']['tt_content']['types']['accessibility_barrier_free_content'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.html_formlabel,
            tx_accessibility_barrier_free_generalInfo_help,
            tx_accessibility_barrier_free_name, tx_accessibility_barrier_free_address, tx_accessibility_barrier_free_phone, tx_accessibility_barrier_free_email, tx_accessibility_barrier_free_contactFormLink,
            --palette--;LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.palettes.notBarrierFreeContent;accessibility_barrier_free_notBarrierFreeContent,
            --palette--;LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.palettes.economic_unreasonable;accessibility_barrier_free_economic_unreasonable,
            --palette--;LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.palettes.barrier_free_addressOfTheEnforcementBody;accessibility_barrier_free_addressOfTheEnforcementBody,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
    ',
    'columnsOverrides' => [
        'bodytext' => [
            'config' => [
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ],
        ],
    ],
];



// Add the tx_accessibility_barrier_free_ fields to the pages table
$temporaryColumns = [
    // Fields that should be RTE fields (sent to f:format.raw)
    'tx_accessibility_barrier_free_generalInfo_help' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_notBarrierFreeContent',
        'config' => [
            'type' => 'accessibility_helptext',
            'description' => file_get_contents(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:accessibility/Resources/Private/Form/HelpTexts/tx_accessibility_barrier_free_generalInfo_help.html'))
        ],
    ],
    'tx_accessibility_barrier_free_notBarrierFreeContent' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_notBarrierFreeContent',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'cols' => 40,
            'rows' => 15,
        ],
    ],
    'tx_accessibility_barrier_free_notBarrierFreeContent_help' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_notBarrierFreeContent',
        'config' => [
            'type' => 'accessibility_helptext',
            'description' => file_get_contents(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:accessibility/Resources/Private/Form/HelpTexts/tx_accessibility_barrier_free_notBarrierFreeContent_help.html'))
        ],
    ],
    'tx_accessibility_barrier_free_economic_unreasonable' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_economic_unreasonable',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'cols' => 40,
            'rows' => 15,
        ],
    ],
    'tx_accessibility_barrier_free_economic_unreasonable_help' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_economic_unreasonable',
        'config' => [
            'type' => 'accessibility_helptext',
            'description' => file_get_contents(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:accessibility/Resources/Private/Form/HelpTexts/tx_accessibility_barrier_free_economic_unreasonable_help.html'))
        ],
    ],
    'tx_accessibility_barrier_free_addressOfTheEnforcementBody' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_addressOfTheEnforcementBody',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'cols' => 40,
            'rows' => 15,
        ],
    ],
    'tx_accessibility_barrier_free_addressOfTheEnforcementBody_help' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_addressOfTheEnforcementBody',
        'config' => [
            'type' => 'accessibility_helptext',
            'description' => file_get_contents(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:accessibility/Resources/Private/Form/HelpTexts/tx_accessibility_barrier_free_addressOfTheEnforcementBody_help.html'))
        ],
    ],
    // Fields that should be single-line text fields
    'tx_accessibility_barrier_free_name' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_name',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
    'tx_accessibility_barrier_free_address' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_address',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
    'tx_accessibility_barrier_free_phone' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_phone',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
    'tx_accessibility_barrier_free_email' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_email',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
    'tx_accessibility_barrier_free_contactFormLink' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:tt_content.tx_accessibility_barrier_free_contactFormLink',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputLink',
            'size' => 30,
            'eval' => 'trim',
            'softref' => 'typolink',
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $temporaryColumns);

// Create a palette for the RTE fields
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'accessibility_barrier_free_notBarrierFreeContent',
    'tx_accessibility_barrier_free_notBarrierFreeContent,tx_accessibility_barrier_free_notBarrierFreeContent_help'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'accessibility_barrier_free_economic_unreasonable',
    'tx_accessibility_barrier_free_economic_unreasonable, tx_accessibility_barrier_free_economic_unreasonable_help'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'accessibility_barrier_free_addressOfTheEnforcementBody',
    'tx_accessibility_barrier_free_addressOfTheEnforcementBody,tx_accessibility_barrier_free_addressOfTheEnforcementBody_help'
);
