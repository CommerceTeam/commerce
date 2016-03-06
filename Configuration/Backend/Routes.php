<?php
use \CommerceTeam\Commerce\Controller;

return [
    'commerce_systemdata_navigation' => [
        'path' => '/commerceteam/commerce/systemdata/navigation',
        'target' => Controller\SystemdataNavigationFrameController::class . '::mainAction'
    ],
    'commerce_systemdata_attribute' => [
        'path' => '/commerceteam/commerce/systemdata/attribute',
        'target' => Controller\SystemdataModuleAttributeController::class . '::mainAction'
    ],
    'commerce_systemdata_manufacturer' => [
        'path' => '/commerceteam/commerce/systemdata/manufacturer',
        'target' => Controller\SystemdataModuleManufacturerController::class . '::mainAction'
    ],
    'commerce_systemdata_supplier' => [
        'path' => '/commerceteam/commerce/systemdata/supplier',
        'target' => Controller\SystemdataModuleSupplierController::class . '::mainAction'
    ],

    'commerce_statistic_complete' => [
        'path' => '/commerceteam/commerce/statistic/complete',
        'target' => Controller\StatisticModuleCompleteAggregationController::class . '::mainAction'
    ],
    'commerce_statistic_incremental' => [
        'path' => '/commerceteam/commerce/statistic/incremental',
        'target' => Controller\StatisticModuleIncrementalAggregationController::class . '::mainAction'
    ],
    'commerce_statistic_show' => [
        'path' => '/commerceteam/commerce/statistic/show',
        'target' => Controller\StatisticModuleShowStatisticsController::class . '::mainAction'
    ],

    // Register move commerce element module
    'move_commerce_element' => [
        'path' => '/record/commerce/move',
        'target' => Controller\MoveElementController::class . '::mainAction'
    ],
];
