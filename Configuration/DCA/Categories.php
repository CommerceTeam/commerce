<?php
namespace CommerceTeam\Commerce\Configuration\Dca;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Implements the dynaflex config for the 'tx_commerce_categories' table
 *
 * Class \CommerceTeam\Commerce\Configuration\Dca\Categories
 *
 * @author 2008 Erik Frister <efrister@marketing-factory.de>
 */
class Categories {
	/**
	 * Rows to check
	 *
	 * @var array
	 */
	public $rowChecks = array();

	/**
	 * Dynamic configuration array
	 *
	 * @var array
	 */
	public $DCA = array(
		0 => array(
			'path' => 'tx_commerce_categories/columns/attributes/config/ds/default',
			'modifications' => array(
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array(
						'table' => 'tx_commerce_attribute_correlationtypes',
						'select' => '*',
						'where' => 'uid = 1',
					),
					'field_config' => array(
						1 => array(
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array(
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_label' => 'title',
								'foreign_table_where' => ' AND has_valuelist = 1 AND multiple = 0 AND sys_language_uid in (0, -1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 30,
								'autoSizeMax' => 20,
								'renderMode' => 'tree',
								'treeConfig' => array(
									'parentField' => 'parent',
									'appearance' => array(
										'expandAll' => TRUE,
										'showHeader' => TRUE,
									),
								),
							),
						),
					),
				),
			),
		),
		1 => array(
			'path' => 'tx_commerce_categories/columns/attributes/config/ds/default',
			'modifications' => array(
				array(
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array(
						'table' => 'tx_commerce_attribute_correlationtypes',
						'select' => '*',
						'where' => 'uid != 1',
					),
					'field_config' => array(
						1 => array(
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array(
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_label' => 'title',
								'foreign_table_where' => ' AND sys_language_uid in (0, -1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 30,
								'autoSizeMax' => 20,
								'renderMode' => 'tree',
								'treeConfig' => array(
									'parentField' => 'parent',
									'appearance' => array(
										'expandAll' => TRUE,
										'showHeader' => TRUE,
									),
								),
							),
						),
					),
				),
			),
		),
	);

	/**
	 * Fields to cleanup
	 *
	 * @var string
	 */
	public $cleanUpField = 'attributes';

	/**
	 * Hooks
	 *
	 * @var array
	 */
	public $hooks = array();
}
