<?php
namespace CommerceTeam\Commerce\Dao;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
 *
 * @author 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
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
     * Database connection.
     *
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $database;

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
    protected $error = array();

    /**
     * Constructor.
     *
     * @param BasicDaoParser $parser Parser
     * @param int $createPid Create pid
     * @param string $dbTable Table
     *
     * @return self
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

        // @todo extract db action into repository
        $database = $this->getDatabaseConnection();
        // execute query
        $database->exec_INSERTquery($this->dbTable, $dbModel);

        // any errors
        $error = $database->sql_error();
        if (!empty($error)) {
            $this->addError(array(
                $error,
                $database->INSERTquery($this->dbTable, $dbModel),
                '$dbModel' => $dbModel,
            ));
        }

        // set object id
        $object->setId($database->sql_insert_id());
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
        $dbWhere = 'uid = ' . (int) $uid;
        $dbModel = $this->parser->parseObjectToModel($object);

        // @todo extract db action into repository
        $database = $this->getDatabaseConnection();

        // execute query
        $database->exec_UPDATEquery($this->dbTable, $dbWhere, $dbModel);

        // any errors
        $error = $database->sql_error();
        if (!empty($error)) {
            $this->addError(array(
                $error,
                $database->UPDATEquery($this->dbTable, $dbWhere, $dbModel),
                '$dbModel' => $dbModel,
            ));
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
        // @todo extract db action into repsitory
        $database = $this->getDatabaseConnection();

        // execute query
        $database->exec_DELETEquery($this->dbTable, 'uid = ' . (int) $uid);

        // any errors
        $error = $database->sql_error();
        if (!empty($error)) {
            $this->addError(array(
                $error,
                $database->DELETEquery($this->dbTable, 'uid = ' . (int) $uid),
            ));
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
        // @todo extract db action into repository
        $database = $this->getDatabaseConnection();

        // execute query
        $res = $database->exec_SELECTquery('*', $this->dbTable, 'uid = ' . (int) $uid . ' AND deleted = 0');

        // insert into object
        $model = $database->sql_fetch_assoc($res);
        if ($model) {
            // parse into object
            $this->parser->parseModelToObject($model, $object);
        } else {
            // no object found, empty obj and id
            $object->clear();
        }

        // free results
        $database->sql_free_result($res);
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


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
