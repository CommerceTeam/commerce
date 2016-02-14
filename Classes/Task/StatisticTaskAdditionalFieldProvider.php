<?php
namespace CommerceTeam\Commerce\Task;

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

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Class \CommerceTeam\Commerce\Task\StatisticTaskAdditionalFieldProvider.
 *
 * @author 2013 Sebastian Fischer <typo3@marketing-factory.de>
 */
class StatisticTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Submitted data.
     *
     * @var array
     */
    protected $submittedData = array();

    /**
     * Aggregation.
     *
     * @var array
     */
    protected $aggregation = array(
        'completeAggregation' => 'tx_commerce_task_statistictask.completeAggregation',
        'incrementalAggregation' => 'tx_commerce_task_statistictask.incrementalAggregation',
    );

    /**
     * Add a multi select box with all available cache backends.
     *
     * @param array $taskInfo Reference to the array containing the info used
     * @param StatisticTask $task When editing, reference to
     *      the current task object. Null when adding.
     * @param SchedulerModuleController $parentObject Reference to the calling object
     *
     * @return array containing the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject)
    {
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
            'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:' . $this->aggregation,
            $taskInfo[$uid]['commerce_statisticTask_aggregation']
        );

        $additionalFields[$fieldId] = array(
            'code' => $fieldHtml,
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:'
                . 'tx_commerce_task_statistictask.selectAggregation',
            'cshKey' => '_MOD_tools_commerce',
            'cshLabel' => $fieldId,
        );

        return $additionalFields;
    }

    /**
     * Checks that all selected backends exist in available backend list.
     *
     * @param array $submittedData Reference to data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to Scheduler module
     *
     * @return bool True if validation was ok, false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject)
    {
        $this->submittedData = $submittedData;
        $validData = true;

        if (!in_array($submittedData[$this->getTaskUid()]['commerce_statisticTask_aggregation'], $this->aggregation)) {
            $validData = false;
        }

        return $validData;
    }

    /**
     * Save selected backends in task object.
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to current task
     *
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $this->submittedData = $submittedData;

        /**
         * Task
         *
         * @var StatisticTask $task
         */
        $task->setSelectedAggregation($submittedData[$this->getTaskUid()]['commerce_statisticTask_aggregation']);
    }

    /**
     * Build select options of available backends and set currently selected backends.
     *
     * @param string $fieldName Field name
     * @param string $fieldId Field id
     * @param array $valuesAndLabels Values and labels
     * @param string|int $selectedValue Selected backends
     *
     * @return string HTML of selectbox options
     */
    protected function renderOptions($fieldName, $fieldId, array $valuesAndLabels, $selectedValue)
    {
        $options = array();

        foreach ($valuesAndLabels as $value => $label) {
            $selected = $value == $selectedValue ? ' selected="selected"' : '';
            $options[] = '<option value="' . $value . '"' . $selected . '>' .
                $this->getLanguageService()->sL($label) . '</option>';
        }

        return '<select name="' . $fieldName . '" id="' . $fieldId . '">' . implode('', $options) . '</select>';
    }

    /**
     * Getter for task uid.
     *
     * @return int
     */
    protected function getTaskUid()
    {
        return $this->submittedData['uid'];
    }


    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
