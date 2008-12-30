<?php
/**
 * Created on 04.11.2008
 * 
 * Implements the dynafley configuration for the 'tx_commerce_articles' extension
 * 
 * @author Erik Frister <efrister@marketing-factory.de>
 */
class tx_commerce_articles_dfconfig {
	
	var $rowChecks = array (
		// add your checks here
	);
	
	var $DCA = array (
		0 => array (
		'path' => 'tx_commerce_articles/columns/attributesedit/config/ds/default',
		'modifications' => array (
			array (
				'method' => 'remove',
				'inside' => 'ROOT/el',
				'element' => 'dummy',
			),
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'fields',
				'source' => 'db',
				'source_type' => 'entry_count',
				'source_config' => array (
					'table' => 'tx_commerce_articles_article_attributes_mm',
					'select' => '*',
					'where' => 'uid_local=###uid###',
					'orderby' => 'sorting'
				),
				'allUserFunc' => 'tx_commerce_attributeEditor->getAttributeEditFields',
			),
		),
	),
	/* This configuration is for the prices sheet. We have to give the user the
	* possibility to add a free number of prices to all products. Each of that
	* prices have it's own access fields, so the user can define different prices
	* for various usergroups.
		*/
	1 => array (
		'path' => 'tx_commerce_articles/columns/prices/config/ds/default',
		'modifications' => array (
			array (
				'method' => 'remove',
				'inside' => 'ROOT/el',
				'element' => 'dummy',
			),
			
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'fields',
				'source' => 'db',
				'source_type' => 'entry_count',
				'source_config' => array (
					'table' => 'tx_commerce_article_prices',
					'select' => 'uid',
					'where' => 'uid_article=###uid### AND deleted=0',
				),
				'field_config' => array (
					0 => array (
						'name' => 'caption_###uid###', 
						'label' => '',
						'config' => array (
							'type' => 'user', 
							'userFunc' => 'tx_commerce_articleCreator->deletePriceButton',
						),
					),
					1 => array (
						'name' => 'price_gross_###uid###',
						'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_gross',
						'config' => array (
							'type' => 'input',
							'size' => '15',
							'eval' => 'double2,nospace',
						),
					),
					2 => array (
						'name' => 'price_net_###uid###',
 						'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_net',
						'config' => Array (
							'type' => 'input',
							'size' => '15',
							'eval' => 'double2,nospace',
						),
					),
					3 => array (
						'name' => 'hidden_###uid###',
 						'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
						'config' => Array (
							'type' => 'check',
							'default' => '0'
						)
					),
					4 => array (
						'name' => 'starttime_###uid###',
 						'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
						'config' => Array (
							'type' => 'input',
							'size' => '8',
							'max' => '20',
							'eval' => 'date',
							'default' => '0',
							// 'checkbox' => '0'
						)
					),
					5 => array (
						'name' => 'endtime_###uid###',
						'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
						'config' => Array (
							'type' => 'input',
							'size' => '8',
							'max' => '20',
							'eval' => 'date',
							// 'checkbox' => '0',
							'default' => '0',
							'range' => Array (
								//'upper' => mktime(0,0,0,12,31,2020),
								'lower' => '' // <-- gets filled dynamically by hook!
							)
						)
					),
					6 => array (
						'name' => 'fe_group_###uid###',
 						'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
						'config' => Array (
							'type' => 'select',
							'items' => Array (
								Array('', 0),
								Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
								Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
								Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
							),
							'foreign_table' => 'fe_groups'
						)
					),
					7 => array (
						'name' => 'purchase_price_###uid###',
 						'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.purchase_price',
						'config' => Array (
							'type' => 'input',
							'size' => '15',
							'eval' => 'double2,nospace',
						),
					),
					8 => array (
						'name' => 'price_scale_amount_start_###uid###',
 						'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_amount_start',
						'config' => Array (
								'type' => 'input',
								'size' => '15',
								'eval' => 'int,nospace,required',
								'range' => array('lower' => 1),
								'default' => 1,
						),
					),
					9 => array (
						'name' => 'price_scale_amount_end_###uid###',
 						'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_amount_end',
						'config' => Array (
								'type' => 'input',
								'size' => '15',
								'eval' => 'int,nospace,required',
								'range' => array('lower' => 1),
								'default' => 1,
						),
					),
				),
			),
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'field',
				'name' => 'createnewlink',
				'label' => '',
				'config' => array(
					'type' => 'user',
					'userFunc' => 'tx_commerce_articleCreator->createNewPriceCB',
				),
				
			),
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'field',
				'name' => 'createnewscalepricesstartamount',
				'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_startamount',
				'config' => array(
					'type' => 'user',
					'userFunc' => 'tx_commerce_articleCreator->createNewScalePricesStartAmount',
				),
			),				
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'field',
				'name' => 'createnewscalepricescount',
				'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_count',
				'config' => array(
					'type' => 'user',
					'userFunc' => 'tx_commerce_articleCreator->createNewScalePricesCount',
				),
			),				
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'field',
				'name' => 'createnewscalepricessteps',
				'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_steps',
				'config' => array(
					'type' => 'user',
					'userFunc' => 'tx_commerce_articleCreator->createNewScalePricesSteps',
				),
			),			
			array (
				'method' => 'add',
				'path' => 'ROOT/el',
				'type' => 'field',
				'name' => 'create_new_scale_prices_fe_group',
				'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.price_scale_fe_group',
				'config' => array(
							'type' => 'select',
							'items' => Array (
								Array('', 0),
								Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
								Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
								Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
							),
							'foreign_table' => 'fe_groups'
				),
			),
			
			
		),
		
	),
	2 => array (
		    'path' => 'tx_commerce_articles/types/0/showitem',
		    'parseXML' => false,
		    'modifications' => array (
					      array (
						     'method' => 'add',
						     'type' => 'append',
						     'condition' => array (
									   'source' => 'language',
									   'if' => 'isEqual',
									   'compareTo' => 'DEF',
									   ),
						     'config' => array (
									'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.prices,prices;;;;1-1-1',
									),
						     ),
					      ),
		    ),
	3 => array (
		'path' => 'tx_commerce_articles/types/0/showitem',
		'parseXML' => false,
		'modifications' => array (
			array (
				'method' => 'add',
				'type' => 'append',
				'config' => array (
					'text' => ',--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_articles.extras'
				),
			),
			 array (
				 'method' => 'move',
				 'type' => 'extraFields',
				 'table' => 'tx_commerce_articles',
			),
		),
	),
	);

	var $cleanUpField = 'attributesedit,prices';
	
	var $hooks = array(
		'EXT:commerce/dcafiles/class.tx_commerce_dcahooks.php:tx_commerce_dcahooks'
	);
}
?>
