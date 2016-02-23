<?php
use \CommerceTeam\Commerce\Controller;

return [
    'CommerceTeam_Commerce_SystemdataNavigation' => [
        'path' => '/commerceteam/commerce/systemdatanavigation',
        'target' => Controller\SystemdataNavigationFrameController::class . '::mainAction'
    ],

    // Register move commerce element module
    'move_commerce_element' => [
        'path' => '/record/commerce/move',
        'target' => Controller\MoveElementController::class . '::mainAction'
    ],
];
