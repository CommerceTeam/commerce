<?php
namespace CommerceTeam\Commerce\Hook;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Form\Element\InlineElement;

/**
 * Class \CommerceTeam\Commerce\Hook\IrreHooks.
 *
 * @author 2008-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class IrreHooks implements \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface
{
    /**
     * Parent object.
     *
     * @var InlineElement
     */
    protected $parentObject;

    /**
     * Initializes this hook object.
     *
     * @param InlineElement $parentObject Calling object
     *
     * @return void
     */
    public function init(&$parentObject)
    {
        $this->parentObject = $parentObject;
    }

    /**
     * Pre-processing to define which control items are enabled or disabled.
     *
     * @param string $parentUid The uid of the parent record (uid or NEW...)
     * @param string $foreignTable The table we create control-icons for
     * @param array $childRecord The current record of that foreign_table
     * @param array $childConfig TCA configuration of the current field
     * @param bool $isVirtual Defines whether the current records is only virtually
     *      shown and not physically part of the parent record
     * @param array $enabledControls Associative array with the enabled control items
     *
     * @return void
     */
    public function renderForeignRecordHeaderControl_preProcess(
        $parentUid,
        $foreignTable,
        array $childRecord,
        array $childConfig,
        $isVirtual,
        array &$enabledControls = null
    ) {
        $settingsFactory = ConfigurationUtility::getInstance();

        if (is_null($enabledControls)) {
            $enabledControls = array('new' => true, 'hide' => true, 'delete' => true);
        } elseif ($settingsFactory->getExtConf('simpleMode') == 1
            && $foreignTable == 'tx_commerce_articles'
            && $parentUid == $settingsFactory->getExtConf('deliveryID')
        ) {
            $enabledControls = array('new' => true, 'hide' => true, 'delete' => true);
        } elseif ($settingsFactory->getExtConf('simpleMode') == 1 && $foreignTable == 'tx_commerce_articles') {
            $enabledControls = array('hide' => true);
        } elseif ($foreignTable == 'tx_commerce_article_prices') {
            $enabledControls = array('new' => true, 'sort' => true, 'hide' => true, 'delete' => true);
        }
    }

    /**
     * Post-processing to define which control items to show.
     * Possibly own icons can be added here.
     *
     * @param string $parentUid The uid of the parent record (uid or NEW...)
     * @param string $foreignTable The table we create control-icons for
     * @param array $childRecord The current record of that foreign_table
     * @param array $childConfig TCA configuration of the current field
     * @param bool $isVirtual Defines whether the current records is only
     *      virtually shown and not physically part of the parent record
     * @param array  $controlItems Associative array with the currently
     *      available control items
     *
     * @return void
     */
    public function renderForeignRecordHeaderControl_postProcess(
        $parentUid,
        $foreignTable,
        array $childRecord,
        array $childConfig,
        $isVirtual,
        array &$controlItems
    ) {
        // registered empty to satisfy interface
    }
}
