<?php
/**
 * Created on 04.11.2008
 * 
 * Implements the dynafley configuration for the 'tx_commerce_products' table
 * 
 * @author Erik Frister <efrister@marketing-factory.de>
 */
class tx_commerce_products_dfconfig {

	var $DCA = array (
		/* This is the configuration for the correlationtype fields on tab "select attributes"
		 * We fetch all correlationtypes from the database and for every ct we create two fields.
		 * The first one is field of type none. The only reason for this field is to display all
		 * attributes from the parent categories the product is assigned to. This field is filled
		 * the tcehooks class.
		 * The second field is a little bit more complex, because the user can select some attributes
		 * from the db here. It's a normal select field which is handled by TYPO3. Only writing the
		 * relations into the database is done in class tcehooks.
		 */
		0 => array (
			'path' => 'tx_commerce_products/columns/attributes/config/ds/default',
			'cleanup' => array (
					    'table' => 'tx_commerce_products',
					    'field' => 'attributes',
					    ),
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
								'foreign_table_where' => '  AND sys_language_uid in (0,-1) AND has_valuelist=1 AND multiple=0 ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 50,
							),
						),
					),
				),
			),
		),
		1 => array (
			'path' => 'tx_commerce_products/columns/attributes/config/ds/default',
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
								'foreign_table_where' => '  AND sys_language_uid in (0,-1) ORDER BY title',
								'size' => 5,
								'minitems' => 0,
								'maxitems' => 50,
							),
						),
					),
				),
			),
		),
		/*
		 * Here we define the fields on "edit attributes" tab. They will be defined by a userfunction.
		 * This userfunction IS NOT the same as the userdefined field thing of TYPO3. It's something
		 * dynaflex related! We fetch all attributes with ct 4 for this product and pass the data to
		 * a userfuntion. This function creates a dynaflex field configuration (Which is actually a
		 * "normal" TYPO3 TCA field configuration) and returns it to dynaflex, which creates the field
		 * in the TCA. Irritated? No problem... ;)
		 */
		2 => array (
			'path' => 'tx_commerce_products/columns/attributesedit/config/ds/default',
			'modifications' => array (
				array (
					'method' => 'add',
					'path' => 'ROOT/el',
					'type' => 'fields',
					'condition' => array (
						'if' => 'hasValues',
						'source' => 'db',
						'table' => 'tx_commerce_products_attributes_mm',
						'select' => 'uid_foreign',
						'where' => 'uid_local = ###uid### AND uid_correlationtype=4',
						'orderby' => 'sorting',
					),
					'source' => 'db',
					'source_config' => array (
						'table' => 'tx_commerce_products_attributes_mm',
						'select' => '*',
						'where' => 'uid_local = ###uid### AND uid_correlationtype=4',
						'orderby' => 'sorting',
					),
					'field_config' => array (
						'singleUserFunc' => 'tx_commerce_attributeEditor->getAttributeEditField',
					),
				),
			),
		),
		/*
		 * At last we have to decide which tabs have to be displayed. We do this with a
		 * dynaflex condition and if it triggers, we append something at the showitem value
		 * in the products TCA.
		 */
		3 => array (
			'path' => 'tx_commerce_products/types/0/showitem',
			'parseXML' => false,
			'modifications' => array (
						  // display the "select attributes only in def language
						  array (
							 'method' => 'add',
							 'type' => 'append',
							 'condition' => array (
									       'source' => 'language',
									       'if' => 'isEqual',
									       'compareTo' => 'DEF',
									       ),
							 'config' => array (
									    'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.select_attributes,attributes;;;;1-1-1',
									    ),
							 ),
						  // add "edit attributes" tab if minimum one attribute with correlationtype 4 exists for this product
						  // this also recognizes attributes from categories of this product.
						  array (
							 'method' => 'add',
							 'type' => 'append',
							 'conditions' => array (
										array (
										       'if' => 'isGreater',
										       'table' => 'tx_commerce_products_attributes_mm pa',
										       'select' => 'COUNT(*)',
										       'where' => 'uid_correlationtype=4 AND uid_local=###uid###',
										       'isXML' => false,
										       'compareTo' => 0,
										       ),
										array (
										       'source' => 'language',
										       'if' => 'isEqual',
										       'compareTo' => 'DEF'
										       ),
										),
							 'config' => array (
									    'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.edit_attributes,attributesedit;;;;1-1-1',
									    ),
							 ),
							 
							// add "localise attributes" tab if minimum one attribute with correlationtype 4 exists for this product
						  	// and we are in a localised view
						  array (
							 'method' => 'add',
							 'type' => 'append',
							 'conditions' => array (
										array (
										       'if' => 'isGreater',
										       'table' => 'tx_commerce_products_attributes_mm pa',
										       'select' => 'COUNT(*)',
										       'where' => 'uid_correlationtype=4 AND uid_local=###uid###',
										       'isXML' => false,
										       'compareTo' => 0,
										       ),
										array (
										       'source' => 'language',
										       'if' => 'notEqual',
										       'compareTo' => 'DEF'
										       ),
										),
							 'config' => array (
									    'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.localedit_attributes,attributesedit;;;;1-1-1',
									    ),
							 ),
						  // add "create articles" tab if minimum one attribute with correlationtype 1 exists for this product
						  // this also recognizes attributes from categories of this product.
						  // The fields on the tab are allready defined in the TCA!
						  
						  array (
							 'method' => 'add',
							 'type' => 'append',
							 'condition' => array (
									       'source' => 'language',
									       'if' => 'isEqual',
									       'compareTo' => 'DEF',
									       ),
							 'config' => array (
									    'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.create_articles,articles;;;;1-1-1',
									    ),
							 ),
							 
							 // add "Localisze Articel" tab if we are in a localised language
							array (
							 'method' => 'add',
							 'type' => 'append',
							  'condition' => array (
	 								        'table' => 'tx_commerce_products',
	 									     'select' => 'l18n_parent',
	 									     'where' => 'uid=###uid###',
	 									     'isXML' => false,
	 									     'if' => 'isGreater',
	 									     'compareTo' => 0,
	 									     ),
	
							 'config' => array (
									    'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.lokalise_articles,articleslok;;;;1-1-1',
									    ),
							 ),
						  array (
							 'method' => 'add',
							 'type' => 'append',
							# 'condition' => array (
							#		       'source' => 'language',
							#		       'if' => 'isEqual',
							#		       'compareTo' => 'DEF',
							#		       ),
							 'config' => array (
								'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.extras' // @important: do NOT EVER change this name; it is used in the mfc_bibus_catalouge extension (ext_tables)
							 ),
						  ),
						  array (
							 'method' => 'move',
							 'type' => 'extraFields',
							 'table' => 'tx_commerce_products',
							 ),
						  ),
			),
	);

	var $cleanUpField = 'attributesedit';
}
?>
