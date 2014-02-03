<?php
/**
 * $Id: tx_commerce_moveordermails.tca.php 459 2006-12-14 18:16:52Z ingo $
 */
 
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
 
$GLOBALS['TCA']['tx_commerce_moveordermails'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_moveordermails']['ctrl'],
	'interface' => Array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,name,mailkind,mailtemplate,htmltemplate,mailcharset,sendername,senderemail,otherreceiver,BCC'
    ),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_moveordermails']['feInterface'],
	'columns' => Array(
		'sys_language_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => Array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array(
				'type' => 'select',
				'items' => Array(
					Array('', 0),
				),
				'foreign_table' => 'tx_commerce_moveordermails',
				'foreign_table_where' => 'AND tx_commerce_moveordermails.pid=###CURRENT_PID### AND tx_commerce_moveordermails.sys_language_uid IN (-1,0)',
				
			)
		),
		'l18n_diffsource' => Array(
			'config' => Array(
				'type' => 'passthrough'
			)
		),
		'hidden' => Array(
            'exclude' => 1,    
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array(
                'type' => 'check',
                'default' => '0'
            )
        ),
		'starttime' => Array(
            'exclude' => 1,    
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0'
            )
        ),
		'endtime' => Array(
            'exclude' => 1,    
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array(
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
				'range' => Array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                )
            )
        ),
		'fe_group' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array(
				'type' => 'select',
				'size' => 5,
				'maxitems' => 50,
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
			
			)
		),
		'name' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.name',        
			'config' => Array(
                'type' => 'input',    
                'size' => '30',    
                'eval' => 'required,trim',
            )
        ),
		'mailkind' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.mailkind',        
			'config' => Array(
                'type' => 'select',
				'items' => Array(
					Array('LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.mailkind.I.0', 0),
					Array('LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.mailkind.I.1', 1),
                ),
                'size' => 1,    
                'maxitems' => 1,
            )
        ),
		'mailtemplate' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.mailtemplate',        
			'config' => Array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',    
                'disallowed' => 'php,php3',    
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],  
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,    
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
		'htmltemplate' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.htmltemplate',        
			'config' => Array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',    
                'disallowed' => 'php,php3',    
                'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],  
                'uploadfolder' => 'uploads/tx_commerce',
                'size' => 3,    
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
		'mailcharset' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.mailcharset',        
			'config' => Array(
                'type' => 'input',    
                'size' => '30',    
                'eval' => 'required,trim',
                'default' => 'utf-8',
            )
        ),             
		'sendername' => Array(
            'exclude' => 1,     
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.sendername',        
			'config' => Array(
                'type' => 'input',    
                'size' => '48',    
                'eval' => 'trim',
            )
        ),
		'senderemail' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.senderemail',        
			'config' => Array(
                'type' => 'input',    
                'size' => '48',    
                'eval' => 'trim',
            )
        ),
		'otherreceiver' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.otherreceiver',        
			'config' => Array(
                'type' => 'input',    
                'size' => '48',    
                'eval' => 'trim',
            )
        ),
		'BCC' => Array(
            'exclude' => 1,       
            'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_moveordermails.BCC',        
			'config' => Array(
                'type' => 'input',    
                'size' => '48',    
                'eval' => 'trim',
            )
        ),
    ),
	'types' => Array(
        '0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource,hidden;;1;;1-1-1, name, mailkind, mailtemplate,htmltemplate, mailcharset, sendername, senderemail, otherreceiver, BCC')
    ),
	'palettes' => Array(
        '1' => Array('showitem' => 'starttime, endtime, fe_group')
    )
);

?>