<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2008 Erik Frister <efrister@marketing-factory.de>
 *  All rights reserved
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Implements the dynaflex config for the 'tx_commerce_categories' extension
 */
class tx_commerce_configuration_dca_categories {
	public $rowChecks = array();

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
								'foreign_table_where' => ' AND has_valuelist=1 AND multiple=0 AND sys_language_uid in (0,-1) ORDER BY title',
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
								'foreign_table_where' => ' AND sys_language_uid in (0,-1) ORDER BY title',
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

	public $cleanUpField = 'attributes';

	public $hooks = array();
}

?>