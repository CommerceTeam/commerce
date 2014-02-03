<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Carsten Lausen <cl@e-netconsulting.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * class basic Dao mapper
 * This class used by the Dao for database storage.
 * It defines how to insert, update, find and delete a transfer object in the database.
 * Extend this class to fit specific needs.
 * This class has no knowledge about the internal design of the model transfer object.
 * Object <-> model (transfer object) mapping and all model design is done by the parser.
 * The class needs a parser for object <-> model (transfer object) mapping.
 */
class Tx_Commerce_Dao_BasicDaoMapper {
	/**
	 * dbtable for persistence
	 *
	 * @var null|string
	 */
	protected $dbTable = '';

	/**
	 * @var t3lib_db
	 */
	protected $database;

	/**
	 * @var Tx_Commerce_Dao_BasicDaoParser
	 */
	protected $parser;

	/**
	 * @var integer
	 */
	protected $createPid = 0;

	/**
	 * @var array
	 */
	protected $error = array();

	/**
	 * @param Tx_Commerce_Dao_BasicDaoParser $parser
	 * @param integer $createPid
	 * @param string $dbTable
	 * @return self
	 */
	public function __construct(&$parser, $createPid = 0, $dbTable = NULL) {
		$this->init();
		$this->parser = & $parser;
		if (!empty($createPid)) {
			$this->createPid = $createPid;
		}
		if (!empty($dbTable)) {
			$this->dbTable = $dbTable;
		}
	}

	/**
	 * @return void
	 */
	protected function init() {
		$this->database = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	public function load(&$object) {
		if ($object->issetId()) {
			$this->dbSelectById($object->getId(), $object);
		}
	}

	/**
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	public function save(&$object) {
		if ($object->issetId()) {
			$this->dbUpdate($object->getId(), $object);
		} else {
			$this->dbInsert($object);
		}
	}

	/**
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	public function remove(&$object) {
		if ($object->issetId()) {
			$this->dbDelete($object->getId(), $object);
		}
	}

	/**
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	protected function dbInsert(&$object) {
		$dbTable = $this->dbTable;
		$dbModel = $this->parser->parseObjectToModel($object);

			// set pid
		$this->parser->setPid($dbModel, $this->createPid);

			// execute query
		$this->database->exec_INSERTquery($dbTable, $dbModel);

			// any errors
		$error = $this->database->sql_error();
		if (!empty($error)) {
			$this->addError(array(
				$error,
				$this->database->INSERTquery($dbTable, $dbModel),
				'$dbModel' => $dbModel
			));
		}

			// set object id
		$object->setId($this->database->sql_insert_id());
	}

	/**
	 * @param integer $uid
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	protected function dbUpdate($uid, &$object) {
		$dbTable = $this->dbTable;
		$dbWhere = 'uid="' . (int) $uid . '"';
		$dbModel = $this->parser->parseObjectToModel($object);

			// execute query
		$this->database->exec_UPDATEquery($dbTable, $dbWhere, $dbModel);

			// any errors
		$error = $this->database->sql_error();
		if (!empty($error)) {
			$this->addError(array(
				$error,
				$this->database->UPDATEquery($dbTable, $dbWhere, $dbModel),
				'$dbModel' => $dbModel
			));
		}
	}

	/**
	 * @param integer $uid
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	protected function dbDelete($uid, &$object) {
		$dbTable = $this->dbTable;
		$dbWhere = 'uid="' . (int) $uid . '"';

			// execute query
		$this->database->exec_DELETEquery($dbTable, $dbWhere);

			// any errors
		$error = $this->database->sql_error();
		if (!empty($error)) {
			$this->addError(array(
				$error,
				$this->database->DELETEquery($dbTable, $dbWhere)
			));
		}

			// remove object itself
		$object->destroy();
	}

	/**
	 * @param integer $uid
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return void
	 */
	protected function dbSelectById($uid, &$object) {
		$dbFields = '*';
		$dbTable = $this->dbTable;
		$dbWhere = '(uid="' . (int) $uid . '")';
		$dbWhere .= 'AND (deleted="0")';

			// execute query
		$res = $this->database->exec_SELECTquery($dbFields, $dbTable, $dbWhere);

			// insert into object
		$model = $this->database->sql_fetch_assoc($res);
		if ($model) {
				// parse into object
			$this->parser->parseModelToObject($model, $object);
		} else {
				// no object found, empty obj and id
			$object->clear();
		}

			// free results
		$this->database->sql_free_result($res);
	}

	/**
	 * @param array $error
	 * @return void
	 */
	protected function addError($error) {
		$this->error[] = $error;
		debug($error, 'error');
		debug($this);
	}

	/**
	 * @return boolean
	 */
	protected function isError() {
		if (empty($this->error)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * @return array|boolean
	 */
	protected function getError() {
		if (empty($this->error)) {
			return FALSE;
		} else {
			return $this->error;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Dao/BasicDaoMapper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Dao/BasicDaoMapper.php']);
}

?>