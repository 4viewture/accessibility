<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746747770] = [
    'nodeName' => 'accessibilityUpdateTextViaAiControl',
    'priority' => 30,
    'class' => \FourViewture\Accessibility\FormEngine\FieldControl\UpdateTextViaAiControl::class
];

// Register static TypoScript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'accessibility',
    'Configuration/TypoScript',
    'Accessibility Barrier Free Content'
);
