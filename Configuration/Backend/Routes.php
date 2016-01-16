<?php

return [
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
