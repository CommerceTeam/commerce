<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDataMapProcessor
{
    /**
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * This is just a constructor to instanciate the backend library.
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function beforeStart(DataHandler $dataHandler)
    {
    }

    /**
     * Remove any parent_category that has the same uid as the category we are
     * going to save.
     *
     * @param array $incomingFieldArray Incoming field array by reference
     * @param int $id Record uid
     *
     * @return array
     */
    public function preProcess(array &$incomingFieldArray, $id)
    {
        return [];
    }

    /**
     * Will overwrite the data because it has been removed - this is because typo3
     * only allows pages to have permissions so far
     * Will also make some checks to see if all permissions are available that are
     * needed to proceed with the datamap.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     */
    public function postProcess($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
    }

    /**
     * After database category handling.
     *
     * @param string $table Table
     * @param string|int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $dataHandler Parent object
     * @param string $status
     */
    public function afterDatabase($table, $id, array $fieldArray, DataHandler $dataHandler, $status)
    {
    }

    /**
     * Return substituted id in case of a new record
     *
     * @param DataHandler $dataHandler
     * @param string|int $id
     *
     * @return int
     */
    protected function getSubstitutedId(DataHandler $dataHandler, $id)
    {
        return isset($dataHandler->substNEWwithIDs[$id]) ? $dataHandler->substNEWwithIDs[$id] : $id;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
