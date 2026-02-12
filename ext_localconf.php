<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

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
