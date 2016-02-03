<?php

return [
    'CommerceTeam_commerce_CategoryNavigation' => [
        'path' => '/commerceteam/commerce/categorynavigation',
        'target' => \CommerceTeam\Commerce\Controller\CategoryNavigationFrameController::class . '::mainAction'
    ],
    'CommerceTeam_commerce_OrderNavigation' => [
        'path' => '/commerceteam/commerce/ordernavigation',
        'target' => \CommerceTeam\Commerce\Controller\OrdersNavigationFrameController::class . '::mainAction'
    ],
    'CommerceTeam_commerce_SystemdataNavigation' => [
        'path' => '/commerceteam/commerce/systemdatanavigation',
        'target' => \CommerceTeam\Commerce\Controller\SystemdataNavigationFrameController::class . '::mainAction'
    ],
    'CommerceTeam_commerce_DataHandler' => [
        'path' => '/commerceteam/commerce/datahandler',
        'target' => \CommerceTeam\Commerce\Utility\DataHandler::class . '::mainAction'
    ],
];
