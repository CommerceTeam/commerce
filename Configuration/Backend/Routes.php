<?php

return [
    'CommerceTeam_commerce_Category' => [
        'path' => '/commerceteam/commerce/category',
        'target' => \CommerceTeam\Commerce\Controller\CategoryModuleController::class . '::render'
    ],
    'CommerceTeam_commerce_Permission' => [
        'path' => '/commerceteam/commerce/permission',
        'target' => \CommerceTeam\Commerce\Controller\PermissionModuleController::class . '::render'
    ],
    'CommerceTeam_commerce_Order' => [
        'path' => '/commerceteam/commerce/order',
        'target' => \CommerceTeam\Commerce\Controller\OrdersModuleController::class . '::render'
    ],
    'CommerceTeam_commerce_Statistic' => [
        'path' => '/commerceteam/commerce/statistic',
        'target' => \CommerceTeam\Commerce\Controller\StatisticModuleController::class . '::render'
    ],
    'CommerceTeam_commerce_Systemdata' => [
        'path' => '/commerceteam/commerce/systemdata',
        'target' => \CommerceTeam\Commerce\Controller\SystemdataModuleController::class . '::render'
    ],

    'CommerceTeam_commerce_CategoryNavigation' => [
        'path' => '/commerceteam/commerce/categorynavigation',
        'target' => \CommerceTeam\Commerce\Controller\CategoryNavigationFrameController::class . '::render'
    ],
    'CommerceTeam_commerce_OrderNavigation' => [
        'path' => '/commerceteam/commerce/ordernavigation',
        'target' => \CommerceTeam\Commerce\Controller\OrdersNavigationFrameController::class . '::render'
    ],
    'CommerceTeam_commerce_SystemdataNavigation' => [
        'path' => '/commerceteam/commerce/systemdatanavigation',
        'target' => \CommerceTeam\Commerce\Controller\SystemdataNavigationFrameController::class . '::render'
    ],
];
