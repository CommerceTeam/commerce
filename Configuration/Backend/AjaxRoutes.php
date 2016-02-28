<?php

/**
 * Definitions for routes provided by EXT:beuser
 */
return [
    // Dispatch the permissions actions
    'user_category_permissions' => [
        'path' => '/user/category/permissions',
        'target' => \CommerceTeam\Commerce\Controller\PermissionAjaxController::class . '::dispatch'
    ],
    // Dispatch the category tree actions
    'commerce_category_tree' => [
        'path' => '/category/category/tree',
        'target' => \CommerceTeam\Commerce\Controller\CategoryAjaxController::class . '::dispatch'
    ]
];
