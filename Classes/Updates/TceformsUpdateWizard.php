<?php
namespace CommerceTeam\Commerce\Updates;

/**
 * This file is part of the TYPO3 commerce project.
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

/**
 * Upgrade wizard which goes through all files referenced in the tt_content.image filed
 * and creates sys_file records as well as sys_file_reference records for the individual usages.
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class TceformsUpdateWizard extends \TYPO3\CMS\Install\Updates\AbstractUpdate
{

    /**
     * Number of records fetched per database query
     * Used to prevent memory overflows for huge databases
     */
    const RECORDS_PER_QUERY = 1000;

    /**
     * @var string
     */
    protected $title = 'Migrate all file relations from commerce tables';

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $storage;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * @var DatabaseConnection
     */
    protected $database;

    /**
     * Table fields to migrate
     *
     * @var array
     */
    protected $tables = array(
        'tx_commerce_articles' => [
            'images' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/article_images/',
            ],
        ],
        'tx_commerce_categories' => [
            'images' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/categories_images/',
            ],
            'teaserimages' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/categories_teaserimages/',
            ],
        ],
        'tx_commerce_products' => [
            'images' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/categories_images/',
            ],
            'teaserimages' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/categories_teaserimages/',
            ],
        ],

        'tx_commerce_attributes' => [
            'icon' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/attribute_icon/',
            ],
        ],
        'tx_commerce_attribute_values' => [
            'icon' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/attribute_values_icon/',
            ],
        ],
        'tx_commerce_manufacturer' => [
            'logo' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/manufacturer_logo/',
            ],
        ],
        'tx_commerce_moveordermails' => [
            'mailtemplate' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/ordermail_mailtemplate/',
            ],
            'htmltemplate' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/ordermail_htmltemplate/',
            ]
        ],
        'tx_commerce_order_types' => [
            'icon' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/order_types_logo/',
            ]
        ],
        'tx_commerce_supplier' => [
            'logo' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/supplier_logo/',
            ]
        ],
        'tx_commerce_user_states' => [
            'icon' => [
                'sourcePath' => 'uploads/tx_commerce',
                // Relative to fileadmin
                'targetPath' => '_migrated/tx_commerce/user_states/',
            ]
        ],
    );

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $registryNamespace = 'TceformsUpdateWizard';

    /**
     * @var array
     */
    protected $recordOffset = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
        $logManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);
        $this->logger = $logManager->getLogger(__CLASS__);
        $this->database = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Initialize the storage repository.
     */
    public function init()
    {
        /** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
        $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $storages = $storageRepository->findAll();
        $this->storage = $storages[0];
        $this->registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
        $this->recordOffset = $this->registry->get($this->registryNamespace, 'recordOffset', array());
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     *
     * @return boolean TRUE if an update is needed, FALSE otherwise
     */
    public function checkForUpdate(&$description)
    {
        $description = 'This update wizard goes through all files that are referenced in the tt_content.image and '
            . 'pages.media / pages_language_overlay.media field and adds the files to the new File Index.<br />'
            . 'It also moves the files from uploads/ to the fileadmin/_migrated/ path.<br /><br />'
            . 'This update wizard can be called multiple times in case it didn\'t finish after running once.';

        if ($this->versionNumber < 6000000) {
            // Nothing to do
            return false;
        }

        $finishedFields = $this->getFinishedFields();
        if (count($finishedFields) === 0) {
            // Nothing done yet, so there's plenty of work left
            return true;
        }

        $numberOfFieldsToMigrate = 0;
        foreach ($this->tables as $table => $tableConfiguration) {
            // find all additional fields we should get from the database
            foreach (array_keys($tableConfiguration) as $fieldToMigrate) {
                $fieldKey = $table . ':' . $fieldToMigrate;
                if (!in_array($fieldKey, $finishedFields)) {
                    $numberOfFieldsToMigrate++;
                }
            }
        }

        return $numberOfFieldsToMigrate > 0;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     *
     * @return boolean TRUE on success, FALSE on error
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        if ($this->versionNumber < 6000000) {
            // Nothing to do
            return true;
        }
        try {
            $this->init();
            $finishedFields = $this->getFinishedFields();
            foreach ($this->tables as $table => $tableConfiguration) {
                // find all additional fields we should get from the database
                foreach ($tableConfiguration as $fieldToMigrate => $fieldConfiguration) {
                    $fieldKey = $table . ':' . $fieldToMigrate;
                    if (in_array($fieldKey, $finishedFields)) {
                        // this field was already migrated
                        continue;
                    }
                    $fieldsToGet = array($fieldToMigrate);
                    if (isset($fieldConfiguration['titleTexts'])) {
                        $fieldsToGet[] = $fieldConfiguration['titleTexts'];
                    }
                    if (isset($fieldConfiguration['alternativeTexts'])) {
                        $fieldsToGet[] = $fieldConfiguration['alternativeTexts'];
                    }
                    if (isset($fieldConfiguration['captions'])) {
                        $fieldsToGet[] = $fieldConfiguration['captions'];
                    }
                    if (isset($fieldConfiguration['links'])) {
                        $fieldsToGet[] = $fieldConfiguration['links'];
                    }

                    if (!isset($this->recordOffset[$table])) {
                        $this->recordOffset[$table] = 0;
                    }

                    do {
                        $limit = $this->recordOffset[$table] . ',' . self::RECORDS_PER_QUERY;
                        $records = $this->getRecordsFromTable($table, $fieldToMigrate, $fieldsToGet, $limit);
                        foreach ($records as $record) {
                            $this->migrateField($table, $record, $fieldToMigrate, $fieldConfiguration, $customMessages);
                        }
                        $this->registry->set($this->registryNamespace, 'recordOffset', $this->recordOffset);
                    } while (count($records) === self::RECORDS_PER_QUERY);

                    // add the field to the "finished fields" if things didn't fail above
                    if (is_array($records)) {
                        $finishedFields[] = $fieldKey;
                    }
                }
            }
            $this->markWizardAsDone(implode(',', $finishedFields));
            $this->registry->remove($this->registryNamespace, 'recordOffset');
        } catch (\Exception $e) {
            $customMessages .= PHP_EOL . $e->getMessage();
        }

        return empty($customMessages);
    }

    /**
     * We write down the fields that were migrated. Like this: tt_content:media
     * so you can check whether a field was already migrated
     *
     * @return array
     */
    protected function getFinishedFields()
    {
        $className = 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard';

        return isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$className]) ?
            explode(',', $GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$className]) : array();
    }

    /**
     * Get records from table where the field to migrate is not empty (NOT NULL and != '')
     * and also not numeric (which means that it is migrated)
     *
     * @param string $table
     * @param string $fieldToMigrate
     * @param array $relationFields
     * @param int $limit Maximum number records to select
     *
     * @throws \RuntimeException
     * @return array
     */
    protected function getRecordsFromTable($table, $fieldToMigrate, $relationFields, $limit)
    {
        $fields = implode(',', array_merge($relationFields, array('uid', 'pid')));
        $deletedCheck = isset($GLOBALS['TCA'][$table]['ctrl']['delete']) ?
            ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0' : '';
        $where = $fieldToMigrate . ' IS NOT NULL' . ' AND ' . $fieldToMigrate . ' != \'\'' . ' AND CAST(CAST('
            . $fieldToMigrate . ' AS DECIMAL) AS CHAR) <> CAST(' . $fieldToMigrate . ' AS CHAR)' . $deletedCheck;
        $result = $this->database->exec_SELECTgetRows($fields, $table, $where, '', 'uid', $limit);
        if ($result === null) {
            throw new \RuntimeException('Database query failed. Error was: ' . $this->database->sql_error());
        }

        return $result;
    }

    /**
     * Migrates a single field.
     *
     * @param string $table
     * @param array $row
     * @param string $fieldname
     * @param array $fieldConfiguration
     * @param string $customMessages
     *
     * @return array A list of performed database queries
     * @throws \Exception
     */
    protected function migrateField($table, $row, $fieldname, $fieldConfiguration, &$customMessages)
    {
        $titleTextContents = array();
        $alternativeTextContents = array();
        $captionContents = array();
        $linkContents = array();

        $fieldItems = GeneralUtility::trimExplode(',', $row[$fieldname], true);
        if (empty($fieldItems) || is_numeric($row[$fieldname])) {
            return array();
        }
        if (isset($fieldConfiguration['titleTexts'])) {
            $titleTextField = $fieldConfiguration['titleTexts'];
            $titleTextContents = explode(LF, $row[$titleTextField]);
        }

        if (isset($fieldConfiguration['alternativeTexts'])) {
            $alternativeTextField = $fieldConfiguration['alternativeTexts'];
            $alternativeTextContents = explode(LF, $row[$alternativeTextField]);
        }
        if (isset($fieldConfiguration['captions'])) {
            $captionField = $fieldConfiguration['captions'];
            $captionContents = explode(LF, $row[$captionField]);
        }
        if (isset($fieldConfiguration['links'])) {
            $linkField = $fieldConfiguration['links'];
            $linkContents = explode(LF, $row[$linkField]);
        }
        $fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
        $queries = array();
        $i = 0;

        if (!PATH_site) {
            throw new \Exception('PATH_site was undefined.');
        }

        $storageUid = (int)$this->storage->getUid();

        foreach ($fieldItems as $item) {
            $fileUid = null;
            $sourcePath = PATH_site . $fieldConfiguration['sourcePath'] . $item;
            $targetDirectory = PATH_site . $fileadminDirectory . $fieldConfiguration['targetPath'];
            $targetPath = $targetDirectory . basename($item);

            // maybe the file was already moved, so check if the original file still exists
            if (file_exists($sourcePath)) {
                if (!is_dir($targetDirectory)) {
                    GeneralUtility::mkdir_deep($targetDirectory);
                }

                // see if the file already exists in the storage
                $fileSha1 = sha1_file($sourcePath);

                $existingFileRecord = $this->database->exec_SELECTgetSingleRow(
                    'uid',
                    'sys_file',
                    'sha1=' . $this->database->fullQuoteStr($fileSha1, 'sys_file') .
                    ' AND storage=' . $storageUid
                );
                // the file exists, the file does not have to be moved again
                if (is_array($existingFileRecord)) {
                    $fileUid = $existingFileRecord['uid'];
                } else {
                    // just move the file (no duplicate)
                    rename($sourcePath, $targetPath);
                }
            }

            if ($fileUid === null) {
                // get the File object if it hasn't been fetched before
                try {
                    // if the source file does not exist, we should just continue, but leave a message in the docs;
                    // ideally, the user would be informed after the update as well.
                    /** @var File $file */
                    $file = $this->storage->getFile($fieldConfiguration['targetPath'] . $item);
                    $fileUid = $file->getUid();
                } catch (\InvalidArgumentException $e) {
                    // no file found, no reference can be set
                    $this->logger->notice(
                        'File ' . $fieldConfiguration['sourcePath'] . $item
                        . ' does not exist. Reference was not migrated.',
                        array('table' => $table, 'record' => $row, 'field' => $fieldname)
                    );

                    $format =
                        'File \'%s\' does not exist. Referencing field: %s.%d.%s. The reference was not migrated.';
                    $message = sprintf(
                        $format,
                        $fieldConfiguration['sourcePath'] . $item,
                        $table,
                        $row['uid'],
                        $fieldname
                    );
                    $customMessages .= PHP_EOL . $message;

                    continue;
                }
            }

            if ($fileUid > 0) {
                $fields = array(
                    // TODO add sorting/sorting_foreign
                    'fieldname' => $fieldname,
                    'table_local' => 'sys_file',
                    // the sys_file_reference record should always placed on the same page
                    // as the record to link to, see issue #46497
                    'pid' => ($table === 'pages' ? $row['uid'] : $row['pid']),
                    'uid_foreign' => $row['uid'],
                    'uid_local' => $fileUid,
                    'tablenames' => $table,
                    'crdate' => time(),
                    'tstamp' => time(),
                    'sorting' => ($i + 256),
                    'sorting_foreign' => $i,
                );
                if (isset($titleTextField)) {
                    $fields['title'] = trim($titleTextContents[$i]);
                }
                if (isset($alternativeTextField)) {
                    $fields['alternative'] = trim($alternativeTextContents[$i]);
                }
                if (isset($captionField)) {
                    $fields['description'] = trim($captionContents[$i]);
                }
                if (isset($linkField)) {
                    $fields['link'] = trim($linkContents[$i]);
                }
                $this->database->exec_INSERTquery('sys_file_reference', $fields);
                $queries[] = str_replace(LF, ' ', $this->database->debug_lastBuiltQuery);
                ++$i;
            }
        }

        // Update referencing table's original field to now contain the count of references,
        // but only if all new references could be set
        if ($i === count($fieldItems)) {
            $this->database->exec_UPDATEquery($table, 'uid=' . $row['uid'], array($fieldname => $i));
            $queries[] = str_replace(LF, ' ', $this->database->debug_lastBuiltQuery);
        } else {
            $this->recordOffset[$table]++;
        }

        return $queries;
    }
}
