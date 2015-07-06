.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



Realurl post var sets configuration
===================================

The following configuration enables urls like
http://www.example.com/de/somepath/product/CategoryName/ProductName.html
This adds an extra .../product/... but adds also the benefit of being able
to add additional shop pages without the need to add more configuration.
So a editor is able to add pages without the need to have a developer
configure more realurl settings.


**ext_localconf.php**::

	$TYPO3_CONF_VARS['EXTCONF']['realurl'] =  array(
		'_DEFAULT' => array(
			'init' => array(
				'enableCHashCache' => 1,
				'appendMissingSlash' => 'ifNotFile',
				'enableUrlDecodeCache' => 1,
				'enableUrlEncodeCache' => 1,
			),
			'fileName' => array(
				'defaultToHTMLsuffixOnPrev' => 1,
				'index' => array(
					'index.html' => array(
						'keyValues' => array(
						),
					),
				),
			),
			'preVars' => array(
				array(
					'GETvar' => 'L',
					'valueMap' => array(
						'de' => '0',
						'en-gb' => '1',
					),
					'valueDefault' => 'de',
				),
			),

			'fixedPostVars' => array(
			),

			'postVarSets' => array(
				'_DEFAULT' => array(
					// commerce configuration
					'product' => array(
						array(
							'GETvar' => 'tx_commerce_pi1[catUid]',
							'lookUpTable' => array(
								'table' => 'tx_commerce_categories',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'maxLength' => 120,
								'useUniqueCache' => 1,
								'addWhereClause' => ' AND NOT deleted',
								'enable404forInvalidAlias' => 1,
								'autoUpdate' => 1,
								'expireDays' => 5,
								'useUniqueCache_conf' => array(
									'spaceCharacter' => '-'
								)
							)
						),
						array(
							'GETvar' => 'tx_commerce_pi1[showUid]',
							'lookUpTable' => array(
								'table' => 'tx_commerce_products',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'maxLength' => 120,
								'useUniqueCache' => 1,
								'addWhereClause' => ' AND NOT deleted',
								'enable404forInvalidAlias' => 1,
								'autoUpdate' => 1,
								'expireDays' => 5,
								'useUniqueCache_conf' => array(
									'spaceCharacter' => '-'
								)
							),
						),
					),

					'plaintext' => array(
						'type' => 'single',
						'keyValues' => array(
							'type' => 99
						)
					),
				),
			),

			'pagePath' => array (
				'type' => 'user',
				'userFunc' => 'EXT:realurl/class.tx_realurl_advanced.php:&tx_realurl_advanced->main',
				'spaceCharacter' => '_',
				'languageGetVar' => 'L',
				'expireDays' => 3,
				'rootpage_id' => 1,
				'segTitleFieldList' => 'tx_realurl_pathsegment,alias,nav_title,title',
			),
		),
	);
