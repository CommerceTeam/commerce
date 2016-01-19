<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "commerce".
 *
 * Auto generated 24-11-2013 13:08
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Commerce',
    'description' => 'TYPO3 commerce shopping system',
    'category' => 'module',
    'author' => 'Ingo Schmitt,Volker Graubaum,Thomas Hempel',
    'author_email' => 'team@typo3-commerce.org',
    'author_company' => 'Marketing Factory Consulting GmbH,e-netconsulting KG,n@work Internet Informationssysteme GmbH',
    'shy' => 0,
    'priority' => '',
    'loadOrder' => '',
    'module' => 'mod_main,mod_category,mod_access,mod_orders,mod_systemdata,mod_statistic',
    'state' => 'stable',
    'internal' => 0,
    'uploadfolder' => 1,
    'createDirs' => 'uploads/tx_commerce/rte',
    'modify_tables' => 'tt_address,fe_users',
    'clearCacheOnLoad' => 1,
    'lockType' => 'L',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'version' => '5.0.0',
    '_md5_values_when_last_written' => '',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.0-7.9.99',
            'tt_address' => '3.0.0-',
            'static_info_tables' => '6.2.1-',
        ),
        'conflicts' => array(
            'mc_autokeywords' => '',
        ),
        'suggests' => array(
        ),
    ),
);
