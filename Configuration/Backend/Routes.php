<?php
use \CommerceTeam\Commerce\Controller;

return [
    'CommerceTeam_commerce_CategoryNavigation' => [
        'path' => '/commerceteam/commerce/categorynavigation',
        'target' => Controller\CategoryNavigationFrameController::class . '::mainAction'
    ],
    'CommerceTeam_commerce_OrderNavigation' => [
        'path' => '/commerceteam/commerce/ordernavigation',
        'target' => Controller\OrdersNavigationFrameController::class . '::mainAction'
    ],
    'CommerceTeam_commerce_SystemdataNavigation' => [
        'path' => '/commerceteam/commerce/systemdatanavigation',
        'target' => Controller\SystemdataNavigationFrameController::class . '::mainAction'
    ],

    // Register move commerce element module
    'move_commerce_element' => [
        'path' => '/record/commerce/move',
        'target' => Controller\MoveElementController::class . '::mainAction'
    ],
];
