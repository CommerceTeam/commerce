.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



Realurl post var sets
=====================

The following configuration enables urls like
http://www.example.com/de/somepath/product/CategoryName/ProductName.html
This adds an extra .../product/... but adds also the benefit of being able
to add additional shop pages without the need to add more configuration.
So a editor is able to add pages without the need to have a developer
configure more realurl settings.


**ext_localconf.php**::

	$TYPO3_CONF_VARS['EXTCONF']['realurl'] =  [
		'_DEFAULT' => [
			'init' => [
				'enableCHashCache' => 1,
				'appendMissingSlash' => 'ifNotFile',
				'enableUrlDecodeCache' => 1,
				'enableUrlEncodeCache' => 1,
			],
			'fileName' => [
				'defaultToHTMLsuffixOnPrev' => 1,
				'index' => [
					'index.html' => [
						'keyValues' => [
						],
					],
				],
			],
			'preVars' => [
				[
					'GETvar' => 'L',
					'valueMap' => [
						'de' => '0',
						'en-gb' => '1',
					],
					'valueDefault' => 'de',
				],
			],

			'fixedPostVars' => [
			],

			'postVarSets' => [
				'_DEFAULT' => [
					// commerce configuration
					'product' => [
						[
							'GETvar' => 'tx_commerce_pi1[catUid]',
							'lookUpTable' => [
								'table' => 'tx_commerce_categories',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'maxLength' => 120,
								'useUniqueCache' => 1,
								'addWhereClause' => ' AND NOT deleted',
								'enable404forInvalidAlias' => 1,
								'autoUpdate' => 1,
								'expireDays' => 5,
								'useUniqueCache_conf' => [
									'spaceCharacter' => '-'
								]
							],
						],
						[
							'GETvar' => 'tx_commerce_pi1[showUid]',
							'lookUpTable' => [
								'table' => 'tx_commerce_products',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'maxLength' => 120,
								'useUniqueCache' => 1,
								'addWhereClause' => ' AND NOT deleted',
								'enable404forInvalidAlias' => 1,
								'autoUpdate' => 1,
								'expireDays' => 5,
								'useUniqueCache_conf' => [
									'spaceCharacter' => '-'
								]
							],
						],
					],

					'plaintext' => [
						'type' => 'single',
						'keyValues' => [
							'type' => 99
						]
					],
				],
			],

			'pagePath' => [
				'type' => 'user',
				'userFunc' => 'EXT:realurl/class.tx_realurl_advanced.php:&tx_realurl_advanced->main',
				'spaceCharacter' => '_',
				'languageGetVar' => 'L',
				'expireDays' => 3,
				'rootpage_id' => 1,
				'segTitleFieldList' => 'tx_realurl_pathsegment,alias,nav_title,title',
			],
		],
	];

