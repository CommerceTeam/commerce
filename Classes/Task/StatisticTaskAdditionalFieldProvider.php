<?php
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
 * Class Tx_Commerce_Task_StatisticTaskAdditionalFieldProvider
 *
 * @author 2013 Sebastian Fischer <typo3@marketing-factory.de>
 */
class Tx_Commerce_Task_StatisticTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {
	/**
	 * Add a multi select box with all available cache backends.
	 *
	 * @param array &$taskInfo Reference to the array containing the info used
	 * @param Tx_Commerce_Task_StatisticTask $task When editing, reference to
	 * 	the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object
	 * @return array containg all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$this->submittedData = $taskInfo;
		$uid = $this->getTaskUid();

		$additionalFields = array();

		// Initialize selected fields
		if (empty($taskInfo[$uid]['commerce_statisticTask_aggregation'])) {
			$taskInfo[$uid]['commerce_statisticTask_aggregation'] = '';
			if ($parentObject->CMD == 'add') {
				// In case of new task, set to incrementalAggregation if it's available
				$taskInfo[$uid]['commerce_statisticTask_aggregation'] = 'incrementalAggregation';
			} elseif ($parentObject->CMD == 'edit') {
				// In case of editing the task, set to currently selected value
				$taskInfo[$uid]['commerce_statisticTask_aggregation'] = $task->getSelectedAggregation();
			}
		}

		$fieldId = 'task_commerce_statisticTask_aggregation';
		$fieldHtml = $this->renderOptions(
			'tx_scheduler[' . $uid . '][commerce_statisticTask_aggregation]',
			$fieldId,
			$this->aggregation,
			$taskInfo[$uid]['commerce_statisticTask_aggregation']
		);

		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.selectAggregation',
			'cshKey' => '_MOD_tools_txcommerceM1',
			'cshLabel' => $fieldId,
		);

		return $additionalFields;
	}

	/**
	 * @var array
	 */
	protected $submittedData = array();

	/**
	 * @var array
	 */
	protected $aggregation = array(
		'completeAggregation' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.completeAggregation',
		'incrementalAggregation' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:tx_commerce_task_statistictask.incrementalAggregation',
	);

	/**
	 * Checks that all selected backends exist in available backend list
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 *
	 * @return bool True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$this->submittedData = $submittedData;
		$uid = $this->getTaskUid();
		$validData = TRUE;

		if (!in_array($submittedData[$uid]['commerce_statisticTask_aggregation'], $this->aggregation)) {
			$validData = FALSE;
		}

		return $validData;
	}

	/**
	 * Save selected backends in task object
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$this->submittedData = $submittedData;
		$uid = $this->getTaskUid();

		/** @var Tx_Commerce_Task_StatisticTask $task */
		$task->setSelectedAggregation($submittedData[$uid]['commerce_statisticTask_aggregation']);
	}

	/**
	 * Build select options of available backends and set currently selected backends
	 *
	 * @param string $fieldName
	 * @param string $fieldId
	 * @param array $valuesAndLabels
	 * @param mixed $selectedValue Selected backends
	 * @return string HTML of selectbox options
	 */
	protected function renderOptions($fieldName, $fieldId, array $valuesAndLabels, $selectedValue) {
		$language = $this->getLanguageService();
		$options = array();

		foreach ($valuesAndLabels as $value => $label) {
			$selected = $value == $selectedValue ? ' selected="selected"' : '';
			$options[] = '<option value="' . $value .  '"' . $selected . '>' . $language->sL($label) . '</option>';
		}

		return '<select name="' . $fieldName . '" id="' . $fieldId . '">' . implode('', $options) . '</select>';
	}

	/**
	 * @return int
	 */
	protected function getTaskUid() {
		return $this->submittedData['uid'];
	}


	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Task/StatisticTaskAdditionalFieldProvider.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Task/StatisticTaskAdditionalFieldProvider.php']);
}
