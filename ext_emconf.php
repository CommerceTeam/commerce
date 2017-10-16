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

$EM_CONF['commerce'] = [
    'title' => 'Commerce',
    'description' => 'TYPO3 commerce shopping system',
    'version' => '6.0.0',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'Ingo Schmitt,Volker Graubaum,Thomas Hempel,Sebastian Fischer',
    'author_email' => 'team@typo3-commerce.org',
    'author_company' => 'Marketing Factory Consulting GmbH,e-netconsulting KG,n@work Internet Informationssysteme GmbH',
    'uploadfolder' => 1,
    'createDirs' => 'uploads/tx_commerce/rte',
    'clearCacheOnLoad' => 1,
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-',
            'typo3' => '8.6.0-8.9.99',
            'tt_address' => '3.0.0-',
            'static_info_tables' => '6.2.1-',
            'beuser' => '8.7.0-',
        ],
    ],
];
