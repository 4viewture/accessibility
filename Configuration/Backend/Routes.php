<?php

return [
    'tx_accessibility_standard' => [
        'path' => '/accessibility/standard',
        'target' => 'FourViewture\\Accessibility\\Backend\\Controller\\StandardController',
    ],
    'tx_accessibility_image_alternative_text' => [
        'path' => '/accessibility/image/alternative/text',
        'target' => 'FourViewture\\Accessibility\\Backend\\Controller\\ImageAlternativeTextController',
    ],
    'tx_accessibility_image_alternative_wizard' => [
        'path' => '/accessibility/image/alternative/wizard',
        'target' => 'FourViewture\\Accessibility\\Backend\\Controller\\Wizard\\AlternativeTextAiWizard',
    ],
];
