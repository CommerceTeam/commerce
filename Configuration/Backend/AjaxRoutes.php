<?php

/**
 * Definitions for routes provided by EXT:beuser
 */
return [
    // Dispatch the permissions actions
    'user_category_permissions' => [
        'path' => '/user/category/permissions',
        'target' => \CommerceTeam\Commerce\Controller\PermissionAjaxController::class . '::dispatch'
    ]
];
