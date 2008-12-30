<?php
/**
 * Created on 04.11.2008
 * 
 * Implements the dynafley configuration for the 'tx_commerce_categories' extension
 * 
 * @author Erik Frister <efrister@marketing-factory.de>
 */
class tx_commerce_categories_dfconfig {
	
	var $rowChecks = array (
		// add your checks here
	);

	var $DCA = array (
		0 => array (
			'path' => 'tx_commerce_categories/columns/attributes/config/ds/default',
			'modifications' => array (
				array (
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array (
						 'table' => 'tx_commerce_attribute_correlationtypes',
						 'select' => '*',
						 'where' => 'uid = 1',
					 ),
					'field_config' => array (
						1 => array (
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array (
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_table_where' => ' AND has_valuelist=1 AND multiple=0 AND sys_language_uid in (0,-1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 30,
							),
						),
					),
				),
			),
		),
		1 => array (
			'path' => 'tx_commerce_categories/columns/attributes/config/ds/default',
			'modifications' => array (
				array (
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'source' => 'db',
					'source_type' => 'entry_count',
					'source_config' => array (
						 'table' => 'tx_commerce_attribute_correlationtypes',
						 'select' => '*',
						 'where' => 'uid != 1',
					 ),
					'field_config' => array (
						1 => array (
							'name' => 'ct_###uid###',
							'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce.ct_###title###',
							'config' => array (
								'type' => 'select',
								'foreign_table' => 'tx_commerce_attributes',
								'foreign_table_where' => ' AND sys_language_uid in (0,-1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 30,
							),
						),
					),
				),
			),
		),
	);

	var $cleanUpField = 'attributes';
	
	var $hooks = array(
	
	);
}
?>
