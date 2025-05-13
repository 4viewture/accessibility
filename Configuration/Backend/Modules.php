<?php

return [
    'accessibility' => [
        'parent' => 'web',
        'position' => ['after' => 'info'],
        'access' => 'user',
        'workspaces' => '*',
        'path' => 'modules/accessibility/standard',
        'extensionName' => 'accessibility',
        'icon' => 'EXT:accessibility/Resources/Public/Icons/Module.svg',
        'labels' => 'LLL:EXT:accessibility/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => \FourViewture\Accessibility\Backend\Controller\StandardController::class,
            ],
            'imageAlternativeText' => [
                'target' => \FourViewture\Accessibility\Backend\Controller\ImageAlternativeTextController::class,
            ],
        ]
    ],
];
