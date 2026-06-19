<?php

declare(strict_types=1);

use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;

return [
    FlagRoutes::PARAM_KEY => [
        'route_prefix' => '/admin/flags',
        'layout' => null,
        'views' => [],
        // Per-slot middleware. Available keys:
        //   all    – prepended to every route (auth, logging, etc.)
        //   list, edit, create  – GET routes
        //   store, update, delete – POST routes
        // RequestBodyParser is added automatically to POST routes (store, update, delete).
        // Set 'body_parser' => false to disable if your pipeline already applies it globally.
        'middlewares' => [],
        'body_parser' => true,
        // Route names used by FlagUrls for link/redirect generation.
        // Override individual keys only when your app uses a different naming convention.
        'route_names' => [
            'list' => FlagRoutes::LIST,
            'edit' => FlagRoutes::EDIT,
            'create' => FlagRoutes::CREATE,
            'store' => FlagRoutes::STORE,
            'update' => FlagRoutes::UPDATE,
            'delete' => FlagRoutes::DELETE,
        ],
    ],
];
