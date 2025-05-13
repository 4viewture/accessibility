<?php

// https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/Backend/JavaScript/ES6/Index.html#requirejs-migration

return [
    'dependencies' => [
        'core',
        'backend'
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@4viewture/accessibility/' => 'EXT:accessibility/Resources/Public/ESM/',
    ],
];
