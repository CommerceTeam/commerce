<?php
namespace CommerceTeam\Commerce\Dao;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use CommerceTeam\Commerce\Domain\Repository\AddressRepository;
use CommerceTeam\Commerce\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class used by the Dao for database storage.
 * It defines how to insert, update, find and delete a transfer object in
 * the database.
 * Extend this class to fit specific needs.
 * This class has no knowledge about the internal design of the model transfer
 * object.
 * Object <-> model (transfer object) mapping and all model design is done by
 * the parser.
 * The class needs a parser for object <-> model (transfer object) mapping.
 *
 * Class \CommerceTeam\Commerce\Dao\BasicDaoMapper
 */
class BasicDaoMapper
{
    /**
     * Table for persistence.
     *
     * @var null|string
     */
    protected $dbTable = '';

    /**
     * Parser.
     *
     * @var BasicDaoParser
     */
    protected $parser;

    /**
     * Create pid.
     *
     * @var int
     */
    protected $createPid = 0;

    /**
     * Error.
     *
     * @var array
     */
    protected $error = [];

    /**
     * @var FrontendUserRepository|AddressRepository
     */
    protected $repository;

    /**
     * Constructor.
     *
     * @param BasicDaoParser $parser Parser
     * @param int $createPid Create pid
     * @param string $dbTable Table
     *
     * @throws \Exception
     */
    public function __construct(BasicDaoParser $parser, $createPid = 0, $dbTable = null)
    {
        $this->init();
        $this->parser = $parser;

        if (!empty($createPid)) {
            $this->createPid = $createPid;
        }

        if (!empty($dbTable)) {
            $this->dbTable = $dbTable;
        }

        switch ($this->dbTable) {
            case 'tt_address':
                $this->repository = GeneralUtility::makeInstance(AddressRepository::class);
                break;

            case 'fe_users':
                $this->repository = GeneralUtility::makeInstance(FrontendUserRepository::class);
                break;

            default:
                throw new \Exception('Unsupported table ' . $this->dbTable . ' found in BasicDaoMapper', 1488868909540);
        }
    }

    /**
     * Initialization.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Load object.
     *
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    public function load(BasicDaoObject $object)
    {
        if ($object->issetId()) {
            $this->dbSelectById($object->getId(), $object);
        }
    }

    /**
     * Save object.
     *
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    public function save(BasicDaoObject $object)
    {
        if ($object->issetId()) {
            $this->dbUpdate($object->getId(), $object);
        } else {
            $this->dbInsert($object);
        }
    }

    /**
     * Remove object.
     *
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    public function remove(BasicDaoObject $object)
    {
        if ($object->issetId()) {
            $this->dbDelete($object->getId(), $object);
        }
    }

    /**
     * Db add object.
     *
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    protected function dbInsert(BasicDaoObject $object)
    {
        $dbModel = $this->parser->parseObjectToModel($object);

        // set pid
        $this->parser->setPid($dbModel, $this->createPid);

        // execute query
        $inserId = $this->repository->addRecord($dbModel);

        // any errors
        if (empty($inserId)) {
            $this->addError([
                'insert ' . $this->dbTable . ' failed',
                '$dbModel' => $dbModel,
            ]);
        }

        // set object id
        $object->setId($inserId);
    }

    /**
     * Db update object.
     *
     * @param int $uid Uid
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    protected function dbUpdate($uid, BasicDaoObject $object)
    {
        $dbModel = $this->parser->parseObjectToModel($object);

        // execute query
        $result = $this->repository->updateRecord($uid, $dbModel);

        // any errors
        if (!$result) {
            $this->addError([
                'update ' . $this->dbTable . ' failed',
                '$dbModel' => $dbModel,
            ]);
        }
    }

    /**
     * Db delete object.
     *
     * @param int $uid Uid
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    protected function dbDelete($uid, BasicDaoObject $object)
    {
        // execute query
        $error = $this->repository->deleteRecord($uid);

        // any errors
        if ($error) {
            $this->addError([
                'delete ' . $this->dbTable . ' failed',
            ]);
        }

        // remove object itself
        $object->destroy();
    }

    /**
     * DB select object by id.
     *
     * @param int $uid Uid
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    protected function dbSelectById($uid, BasicDaoObject $object)
    {
        // insert into object
        $model = $this->repository->findByUid($uid);
        if ($model) {
            // parse into object
            $this->parser->parseModelToObject($model, $object);
        } else {
            // no object found, empty obj and id
            $object->clear();
        }
    }

    /**
     * Add error message.
     *
     * @param array $error Error
     *
     * @return void
     */
    protected function addError(array $error)
    {
        $this->error[] = $error;
    }

    /**
     * Check if error was raised.
     *
     * @return bool
     */
    protected function isError()
    {
        return !empty($this->error);
    }

    /**
     * Get error.
     *
     * @return array|bool
     */
    protected function getError()
    {
        return $this->error ?: false;
    }
}
