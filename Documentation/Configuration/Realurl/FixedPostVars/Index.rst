.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



Realurl fixed post vars
=======================

The following configuration enables urls like
http://www.example.com/de/somepath/CategoryName/ProductName.html
This prevents an extra .../product/...


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
				'txcommerceDetailConfiguration' => [
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
						]
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
				// uid of the detail commerce page on live
				'62' => 'txcommerceDetailConfiguration',
				'217' => 'txcommerceDetailConfiguration',
			],

			'postVarSets' => [
				'_DEFAULT' => [
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

