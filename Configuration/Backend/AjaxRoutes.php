<?php

/**
 * Definitions for routes provided by EXT:commerce
 */
return [
    // Dispatch the permissions actions
    'user_category_permissions' => [
        'path' => '/user/category/permissions',
        'target' => \CommerceTeam\Commerce\Controller\PermissionAjaxController::class . '::dispatch'
    ],
    // Dispatch the category tree actions
    'commerce_category_tree' => [
        'path' => '/commerce/category/tree',
        'target' => \CommerceTeam\Commerce\Controller\CategoryAjaxController::class . '::dispatch'
    ]
];
