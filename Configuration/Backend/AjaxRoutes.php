<?php

declare(strict_types=1);

use FourViewture\Accessibility\Backend\Controller\AiProxyController;

return [
    'accessibility_ai_describe' => [
        'path' => '/accessibility/ai/describe',
        'target' => AiProxyController::class . '::describe',
        'access' => 'user',
        'methods' => ['POST'],
    ],
    'accessibility_ai_status' => [
        'path' => '/accessibility/ai/status/',
        'target' => AiProxyController::class . '::status',
        'access' => 'user',
        'methods' => ['GET'],
    ],
    'accessibility_ai_result' => [
        'path' => '/accessibility/ai/result/',
        'target' => AiProxyController::class . '::result',
        'access' => 'user',
        'methods' => ['GET'],
    ],
];
