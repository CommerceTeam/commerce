<?php
namespace CommerceTeam\Commerce\Controller;
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

use \CommerceTeam\Commerce\Factory\HookFactory;

/**
 * Plugin 'addresses' for the 'commerce' extension.
 * This class handles all the address stuff, like creating, editing and deleting.
 *
 * Class \CommerceTeam\Commerce\Controller\AddressesController
 *
 * @author 2006-2011 Thomas Hempel <thomas@work.de>
 */
class AddressesController extends BaseController {
	/**
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'tx_commerce_pi4';

	/**
	 * User
	 *
	 * @var null
	 */
	public $user = NULL;

	/**
	 * Addresses
	 *
	 * @var array
	 */
	public $addresses = array();

	/**
	 * Holds form error messages
	 *
	 * @var array
	 */
	protected $formError = array();

	/**
	 * Field list
	 *
	 * @var array
	 */
	public $fieldList = array();

	/**
	 * System message
	 *
	 * @var string
	 */
	public $sysMessage = '';

	/**
	 * System message is error
	 *
	 * @var bool
	 */
	protected $sysMessageIsError = FALSE;

	/**
	 * Instance of static info view helper
	 *
	 * @var \SJBR\StaticInfoTables\PiBaseApi
	 */
	public $staticInfo;

	/**
	 * Main method. Starts the magic...
	 *
	 * @param string $content Content of this plugin
	 * @param array $conf TS configuration for this plugin
	 *
	 * @return string Compiled content
	 */
	public function main($content, array $conf = array()) {
		$this->init($conf);

		if (!$this->getFrontendController()->loginUser) {
			return $this->noUser();
		}

		if (isset($this->piVars['check'])) {
			$formValid = $this->checkAddressForm();
		} else {
			$formValid = FALSE;
		}

		if ($formValid && isset($this->piVars['check']) && (int)$this->piVars['backpid'] != $this->getFrontendController()->id) {
			unset($this->piVars['check']);
			header('Location: ' .
				\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(
					$this->pi_getPageLink((int)$this->piVars['backpid'],
					'',
					array(
						'tx_commerce_pi3[addressType]' => (int) $this->piVars['addressType'],
						$this->prefixId . '[addressid]' => (int) $this->piVars['addressid'])
					)
				)
			);
		}

		switch (strtolower($this->piVars['action'])) {
			case 'new':
				if ($formValid) {
					$this->sysMessage = $this->pi_getLL('message_address_new');
					$this->saveAddressData(TRUE, (int) $this->piVars['addressType']);
					$content .= $this->getListing();
					break;
				}
				$content .= $this->getAddressForm('new', (int) $this->piVars['addressid'], $this->conf);
				break;

			case 'delete':
				$addresses = $this->getAddresses(
					(int) $this->user['uid'],
					(int) $this->addresses[$this->piVars['addressid']]['tx_commerce_address_type_id']
				);
				if (count($addresses) <= $this->conf['minAddressCount']) {
					$this->sysMessage = $this->pi_getLL('message_cant_delete');
					$this->sysMessageIsError = TRUE;
					$content .= $this->getListing();
					break;
				}
				if ($this->piVars['confirmed'] == 'yes') {
					$this->deleteAddress();
					$content .= $this->getListing();
					break;
				}
				$content .= $this->deleteAddressQuestion();
				break;

			case 'edit':
				if ($formValid) {
					$this->sysMessage = $this->pi_getLL('message_address_changed');
					$content .= $this->getListing();
					break;
				}
				$content .= $this->getAddressForm('edit', (int) $this->piVars['addressid'], $this->conf);
				break;

			case 'listing':
			default:
				if ($formValid) {
					$this->saveAddressData(FALSE, (int) $this->piVars['addressType']);
				}
				$content .= $this->getListing();
		}

		// add removal of remaining empty markers
		$content = $this->cObj->substituteMarkerArray($content, array(), '', TRUE, TRUE);

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Initialization
	 *
	 * @param array $conf TS configuration for this template
	 * @param bool $getAddresses If this is set to TRUE, this method will fetch
	 * 		all addresses into $this->addresses (Default is TRUE)
	 *
	 * @return void
	 */
	public function init(array $conf, $getAddresses = TRUE) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		/**
		 * Static info tables plugin
		 *
		 * @var \SJBR\StaticInfoTables\PiBaseApi $staticInfo
		 */
		$staticInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('SJBR\\StaticInfoTables\\PiBaseApi');
		$staticInfo->init();
		$this->staticInfo = $staticInfo;

		$addressType = 1;
		if (!empty($this->piVars['addressType'])) {
			$addressType = (int) $this->piVars['addressType'];
		}

		switch ($addressType) {
			case '2':
				$addressType = 'delivery';
				break;

			default:
				$addressType = 'billing';
		}

		if (!is_array($this->conf['formFields.'])) {
			if (is_array($this->conf[$addressType . '.']['formFields.'])) {
				$this->conf['formFields.'] = $this->conf[$addressType . '.']['formFields.'];
			}
		}

		$this->fieldList = $this->parseFieldList($this->conf['formFields.']);

		// Get the template
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		// Check for logged in user
		if (!empty($this->getFrontendUser()->user)) {
			$this->user = $this->getFrontendUser()->user;
		}

		if (isset($this->piVars['check']) && $this->piVars['action'] == 'edit' && $this->checkAddressForm()) {
			$this->saveAddressData(FALSE, (int) $this->piVars['addressType']);
		}

		// Get addresses of this user
		if ($getAddresses) {
			$this->addresses = $this->getAddresses((int) $this->user['uid']);
		}
	}

	/**
	 * Is called whenever the address handling is called without a logged in fe_user.
	 * Currently this is just a dummy with no function.
	 *
	 * @return string
	 */
	protected function noUser() {
		$template = $this->cObj->getSubpart($this->templateCode, '###NOT_LOGGED###');

		$markerArray = array(
			'###NOT_LOGGED_IN###' => $this->pi_getLL('not_logged_in')
		);

		return $this->cObj->substituteMarkerArray($template, $markerArray);
	}

	/**
	 * Returns the listing HTML of addresses.
	 *
	 * @param int $addressType Type of addresses that should be returned.
	 * 		If this is 0 all types will be returned
	 * @param bool $createHiddenFields Create hidden fields
	 * @param string $hiddenFieldPrefix Prefix for field names
	 * @param bool $selectAddressId Adress ID which should be selected by default
	 *
	 * @throws \Exception Thrown if form fields are undefined
	 * @return string HTML with addresses
	 */
	public function getListing($addressType = 0, $createHiddenFields = FALSE, $hiddenFieldPrefix = '', $selectAddressId = FALSE) {
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'getListing');

		if ($this->conf[$addressType . '.']['subpartMarker.']['listWrap']) {
			$tplBase = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$addressType . '.']['subpartMarker.']['listWrap'])
			);
		} else {
			$tplBase = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_LISTING###');
		}

		if ($this->conf[$addressType . '.']['subpartMarker.']['listItem']) {
			$tplItem = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$addressType . '.']['subpartMarker.']['listItem'])
			);
		} else {
			$tplItem = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_ITEM###');
		}

		if (!is_array($this->conf['formFields.'])) {
			if (is_array($this->conf[$addressType . '.']['formFields.'])) {
				$this->conf['formFields.'] = $this->conf[$addressType . '.']['formFields.'];
			}
		}

		// Set prefix if not set
		if (empty($hiddenFieldPrefix)) {
			$hiddenFieldPrefix = $this->prefixId;
		}
		$editAddressId = 0;
		if ($this->piVars['addressid']) {
			// Set var editAddressId for checked
			$editAddressId = (int)$this->piVars['addressid'];
		} elseif ($selectAddressId) {
			$editAddressId = (int)$selectAddressId;
		}

		// Unset some piVars we don't need here
		unset($this->piVars['check']);
		unset($this->piVars['addressid']);
		unset($this->piVars['ismainaddress']);

		foreach ($this->fieldList as $name) {
			unset($this->piVars[$name]);
		}

		// Get all addresses for the desired address types
		$addressTypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['selectAddressTypes']);

		// Count different address types
		$addressTypeCounter = array();
		foreach ($this->addresses as $address) {
			$addressTypeCounter[$address['tx_commerce_address_type_id']]++;
		}

		$addressItems = '';

		$addressFound = FALSE;
		foreach ($this->addresses as $address) {
			if ($addressType > 0 && $address['tx_commerce_address_type_id'] != $addressType) {
				continue;
			}

			$itemMarkerArray = array();
			$linkMarkerArray = array();

			// Fill marker array
			$address = \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($address);
			foreach ($address as $key => $value) {
				$valueHidden = '';
				$upperKey = strtoupper($key);

				if ($this->conf['hideEmptyFields'] && empty($value)) {
					continue;
				}

				if ($value === '') {
					$value = $this->conf['emptyFieldSign'];
				}

				// Get value from database if the field is a select box
				if ($this->conf['formFields.'][$key . '.']['type'] == 'select' &&
						strlen($this->conf['formFields.'][$key . '.']['table']) > 0) {
					$fieldConfig = $this->conf['formFields.'][$key . '.'];
					$table = $fieldConfig['table'];
					$select = $fieldConfig['value'] . '=\'' . $value . '\'' . $this->cObj->enableFields($fieldConfig['table']);
					$fields = $fieldConfig['label'] . ' AS label,';
					$fields .= $fieldConfig['value'] . ' AS value';

					$value = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
						$fields,
						$table,
						$select
					);

					$valueHidden = $value['value'];
					$value = $value['label'];
				} elseif (
					$this->conf['formFields.'][$key . '.']['type'] == 'select'
					&& is_array($this->conf['formFields.'][$key . '.']['values.'])
				) {
					$valueHidden = $value;
					$value = $this->conf['formFields.'][$key . '.']['values.'][$value];
				} elseif ($this->conf['formFields.'][$key . '.']['type'] == 'select') {
					throw new \Exception('Neither table nor value-list defined for select field ' . $key, 1304333953);
				}

				if ($this->conf['formFields.'][$key . '.']['type'] == 'static_info_tables') {
					$fieldConfig = $this->conf['formFields.'][$key . '.'];
					$field = $fieldConfig['field'];
					$valueHidden = $value;
					$value = $this->staticInfo->getStaticInfoName($field, $value);
				}

				$hidden = '';
				if ($createHiddenFields) {
					$hidden = '<input type="hidden" name="' . $hiddenFieldPrefix . '[' . $address['uid'] . '][' . $key .
						']" value="' . ($valueHidden ? $valueHidden : $value) . '" />';
				}

				$itemMarkerArray['###LABEL_' . $upperKey . '###'] = $this->pi_getLL('label_' . $key);
				$itemMarkerArray['###' . $upperKey . '###'] = $value . $hidden;
			}

			// Create a pivars array for merging with link to edit page
			if ($this->conf['editAddressPid'] > 0) {
				$piArray = array('backpid' => $this->getFrontendController()->id);
				$linkTarget = $this->conf['editAddressPid'];
			} else {
				$piArray = array('backpid' => $this->getFrontendController()->id);
				$linkTarget = $this->conf['addressMgmPid'];
			}

			// Set delete link only if addresses may be deleted, otherwise set it empty
			if ((int)$addressTypeCounter[$address['tx_commerce_address_type_id']] > (int)$this->conf['minAddressCount']) {
				$linkMarkerArray['###LINK_DELETE###'] = explode(
					'|',
					$this->pi_linkTP_keepPIvars('|', array('action' => 'delete', 'addressid' => $address['uid']))
				);
				$itemMarkerArray['###LABEL_LINK_DELETE###'] = $this->cObj->stdWrap(
					$this->pi_getLL('label_link_delete'),
					$this->conf['deleteLinkWrap.']
				);
			} else {
				$linkMarkerArray['###LINK_DELETE###'][0] = '';
				$linkMarkerArray['###LINK_DELETE###'][1] = '';
				$itemMarkerArray['###LABEL_LINK_DELETE###'] = '';
			}

			$linkMarkerArray['###LINK_EDIT###'] = explode('|', $this->pi_linkTP_keepPIvars(
					'|',
					array_merge(
						$piArray,
						array(
							'action' => 'edit',
							'addressid' => $address['uid'],
							'addressType' => $address['tx_commerce_address_type_id']
						)
					), FALSE, FALSE, $linkTarget
				));

			$itemMarkerArray['###LABEL_LINK_EDIT###'] = $this->cObj->stdWrap(
				$this->pi_getLL('label_link_edit'),
				$this->conf['editLinkWrap.']
			);

			// add an edit radio button, checked selected previously
			$itemMarkerArray['###SELECT###'] = '<input type="radio" ';

			if (($editAddressId == $address['uid']) || (empty($editAddressId) && $address['tx_commerce_is_main_address'])) {
				$itemMarkerArray['###SELECT###'] .= 'checked="checked" ';
			}

			$itemMarkerArray['###SELECT###'] .= 'name="' . $hiddenFieldPrefix . '[address_uid]" value="' . $address['uid'] . '" />';

			foreach ($hooks as $hookObj) {
				if (method_exists($hookObj, 'processAddressMarker')) {
					$itemMarkerArray = $hookObj->processAddressMarker($itemMarkerArray, $address, $piArray, $this);
				}
			}

			$addressFound = TRUE;

			$addressItems[$address['tx_commerce_address_type_id']] .= $this->substituteMarkerArrayNoCached(
				$tplItem,
				$itemMarkerArray,
				array(),
				$linkMarkerArray
			);
		}

		$linkMarkerArray = array();

		// Create a pivars array for merging with link to edit page
		if ($this->conf['editAddressPid'] > 0) {
			$piArray = array('backpid' => $this->getFrontendController()->id);
			$linkTarget = $this->conf['editAddressPid'];
		} else {
			$piArray = array();
			$linkTarget = $this->conf['addressMgmPid'];
		}

		// Create links and labels for every address type
		if ($addressType == 0) {
			foreach ($addressTypes as $addressType) {
				$baseMarkerArray['###ADDRESS_ITEMS_OF_TYPE_' . $addressType . '###'] = $addressItems[$addressType];
				$baseMarkerArray['###LABEL_ADDRESSES_OF_TYPE_' . $addressType . '###'] = $this->pi_getLL(
					'label_addresses_of_type_' . $addressType
				);
				$linkMarkerArray['###LINK_NEW_TYPE_' . $addressType . '###'] = explode('|', $this->pi_linkTP_keepPIvars(
						'|',
						array_merge(
							$piArray,
							array(
								'action' => 'new',
								'addressType' => $addressType
							)
						), FALSE, FALSE, $linkTarget
					));

				$baseMarkerArray['###LABEL_LINK_NEW_TYPE_' . $addressType . '###'] = $this->cObj->stdWrap(
					$this->pi_getLL('label_link_new_type_' . $addressType),
					$this->conf['newLinkWrap.']
				);
			}
		} else {
			$baseMarkerArray['###ADDRESS_ITEMS###'] = $addressItems[$addressType];
			$linkMarkerArray['###LINK_NEW###'] = explode('|', $this->pi_linkTP_keepPIvars(
					'|',
					array_merge(
						$piArray,
						array('action' => 'new', 'addressType' => $addressType)
					), FALSE, FALSE, $linkTarget
				));
			$baseMarkerArray['###LABEL_LINK_NEW###'] = $this->cObj->stdWrap(
				$this->pi_getLL('label_link_new'),
				$this->conf['newLinkWrap.']
			);
		}

		if (!$addressFound) {
			$baseMarkerArray['###NO_ADDRESS###'] = $this->cObj->stdWrap(
				$this->pi_getLL('label_no_address'),
				$this->conf['noAddressWrap.']
			);
		} else {
			$baseMarkerArray['###NO_ADDRESS###'] = '';
		}

		// Fill sysMessage marker if set
		if (!empty($this->sysMessage)) {
			$baseMarkerArray['###SYS_MESSAGE###'] = $this->cObj->stdWrap(
				$this->sysMessage,
				$this->sysMessageIsError ? $this->conf['sysErrorMessageWrap.'] : $this->conf['sysMessageWrap.']
			);
		} else {
			$baseMarkerArray['###SYS_MESSAGE###'] = '';
		}

		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'processListingMarker')) {
				$hookObj->processListingMarker($baseMarkerArray, $linkMarkerArray, $addressItems, $addressType, $piArray, $this);
			}
		}

		// Replace markers and return content
		return $this->substituteMarkerArrayNoCached($tplBase, $baseMarkerArray, array(), $linkMarkerArray);
	}

	/**
	 * Returns HTML form for a single address. The fields are fetched from
	 * tt_address and configured in TS.
	 *
	 * @param string $action Action that should be performed (can be "new" or "edit")
	 * @param int $addressUid UID of the page where the addresses are stored
	 * @param array $config Configuration array for all fields
	 *
	 * @return string HTML code with the form for editing an address
	 */
	protected function getAddressForm($action = 'new', $addressUid = NULL, array $config) {
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'getAddressForm');

		$addressType = 1;
		if (!empty($this->piVars['addressType'])) {
			$addressType = (int) $this->piVars['addressType'];
		}

		switch ($addressType) {
			case '2':
				$addressType = 'delivery';
				break;

			default:
				$addressType = 'billing';
		}

		// Build query to select an address from the database if user is logged in
		$addressData = ($addressUid != NULL) ? $this->addresses[$addressUid] : array();

		if ($this->formErrors()) {
			$addressData = $this->piVars;
		}
		$addressData = \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($addressData);
		if ($addressData['tx_commerce_address_type_id'] == NULL) {
			$addressData['tx_commerce_address_type_id'] = (int)$this->piVars['addressType'];
		}

		// Get the templates
		if ($this->conf[$addressType . '.']['subpartMarker.']['editWrap']) {
			$tplBase = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$addressType . '.']['subpartMarker.']['editWrap'])
			);
		} else {
			$tplBase = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_EDIT###');
		}
		if ($this->conf[$addressType . '.']['subpartMarker.']['editItem']) {
			$tplForm = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$addressType . '.']['subpartMarker.']['editItem'])
			);
		} else {
			$tplForm = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_EDIT_FORM###');
		}
		if ($this->conf[$addressType . '.']['subpartMarker.']['editField']) {
			$tplField = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$addressType . '.']['subpartMarker.']['editField'])
			);
		} else {
			$tplField = $this->cObj->getSubpart($this->templateCode, '###SINGLE_INPUT###');
		}

		// Create form fields
		$fieldsMarkerArray = array();
		foreach ($this->fieldList as $fieldName) {
			$fieldMarkerArray = array();
			$lowerName = strtolower($fieldName);

			// Get field label
			$fieldLabel = $this->pi_getLL('label_' . $lowerName, $fieldName);

			// Check if the field is manadatory and append the mandatory sign to the label
			if ($config['formFields.'][$fieldName . '.']['mandatory'] == '1') {
				$fieldLabel .= ' ' . $config['mandatorySign'];
			}

			// Insert error message for this specific field
			if (strlen($this->getFormError($fieldName)) > 0) {
				$fieldMarkerArray['###FIELD_ERROR###'] = $this->getFormError($fieldName);
			} else {
				$fieldMarkerArray['###FIELD_ERROR###'] = '';
			}

			// Create input field
			// In this version we only create some simple text fields.
			$fieldMarkerArray['###FIELD_INPUT###'] = $this->getInputField(
				$fieldName,
				$config['formFields.'][$fieldName . '.'], $addressData[$fieldName]
			);

			// Get field item
			$fieldsMarkerArray['###FIELD_' . strtoupper($fieldName) . '###'] = $this->cObj->substituteMarkerArray(
				$tplField,
				$fieldMarkerArray
			);
			$fieldsMarkerArray['###LABEL_' . strtoupper($fieldName) . '###'] = $fieldLabel;
		}

		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'processAddressfieldsMarkerArray')) {
				$fieldsMarkerArray = $hookObj->processAddressfieldsMarkerArray(
					$fieldsMarkerArray, $tplField, $addressData, $action, $addressUid, $config, $this
				);
			}
		}

		// Merge fields with form template
		$formCode = $this->cObj->substituteMarkerArray($tplForm, $fieldsMarkerArray);

		// Create submit button and some hidden fields
		$submitCode = '<input type="hidden" name="' . $this->prefixId . '[action]" value="' . $action . '" />';
		$submitCode .= '<input type="hidden" name="' . $this->prefixId . '[addressid]" value="' . $addressUid . '" />';
		$submitCode .= '<input type="hidden" name="' . $this->prefixId . '[addressType]" value="' .
			$addressData['tx_commerce_address_type_id'] . '" />';
		$submitCode .= '<input type="submit" name="' . $this->prefixId . '[check]" value="' .
			$this->pi_getLL('label_submit_edit') . '" />';

		// Create a checkbox where the user can select if the address is his main
		// address / Changed to label and field
		$isMainAddressCodeField = '<input type="checkbox" name="' . $this->prefixId . '[ismainaddress]"';
		if ($addressData['tx_commerce_is_main_address'] || $addressData['ismainaddress']) {
			$isMainAddressCodeField .= ' checked="checked"';
		}
		$isMainAddressCodeField .= ' />';
		$isMainAddressCodeLabel = $this->pi_getLL('label_is_main_address');

		$baseMarkerArray = array();
		// Fill additional information
		if ($addressData['tx_commerce_address_type_id'] == 1) {
			$baseMarkerArray['###MESSAGE_EDIT###'] = $this->pi_getLL('message_edit_billing');
		} elseif ($addressData['tx_commerce_address_type_id'] == 2) {
			$baseMarkerArray['###MESSAGE_EDIT###'] = $this->pi_getLL('message_edit_delivery');
		} else {
			$baseMarkerArray['###MESSAGE_EDIT###'] = $this->pi_getLL('message_edit_unknown');
		}

		// Fill the marker
		$baseMarkerArray['###ADDRESS_FORM_FIELDS###'] = $formCode;
		$baseMarkerArray['###ADDRESS_FORM_SUBMIT###'] = $submitCode;
		$baseMarkerArray['###ADDRESS_FORM_IS_MAIN_ADDRESS_FIELD###'] = $isMainAddressCodeField;
		$baseMarkerArray['###ADDRESS_FORM_IS_MAIN_ADDRESS_LABEL###'] = $isMainAddressCodeLabel;

		$baseMarkerArray['###ADDRESS_TYPE###'] = $this->pi_getLL('label_address_of_type_' . $this->piVars['addressType']);

		// Get action link
		if ((int) $this->piVars['backpid'] > 0) {
			$link = $this->pi_linkTP_keepPIvars_url();
		} else {
			$link = '';
		}

		$baseMarkerArray['###ADDRESS_FORM_BACK###'] = $this->pi_linkToPage(
			$this->pi_getLL('label_form_back', 'back'),
			$this->piVars['backpid'],
			'',
			array(
				'tx_commerce_pi3' => array(
					'step' => $this->getFrontendUser()->getKey(
						'ses',
						\CommerceTeam\Commerce\Utility\GeneralUtility::generateSessionKey('currentStep')
					)
				)
			)
		);

		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'processAddressFormMarker')) {
				$hookObj->processAddressFormMarker($baseMarkerArray, $action, $addressUid, $addressData, $config, $this);
			}
		}

		return '<form method="post" action="' . $link . '" ' . $this->conf[$addressType . '.']['formParams'] . '>' .
			$this->cObj->substituteMarkerArray($tplBase, $baseMarkerArray) . '</form>';
	}

	/**
	 * Returns HTML code for a confirmation if the user wants to delete
	 * one of his addresses.
	 *
	 * @return string The HTML source with the delete confirmation form
	 */
	protected function deleteAddressQuestion() {
		$tplBase = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_DELETE###');

		// Fill address data to marker
		foreach ($this->fieldList as $name) {
			$baseMarkerArray['label_' . $name] = $this->pi_getLL('label_' . $name);
			$baseMarkerArray[$name] = $this->addresses[(int) $this->piVars['addressid']][$name];
		}

		$baseMarkerArray['QUESTION'] = $this->pi_getLL('question_delete');
		$baseMarkerArray['YES'] = $this->cObj->stdWrap($this->pi_getLL('label_submit_yes'), $this->conf['yesLinkWrap.']);
		$baseMarkerArray['NO'] = $this->cObj->stdWrap($this->pi_getLL('label_submit_no'), $this->conf['noLinkWrap.']);
		$linkMarkerArray['###LINK_YES###'] = explode(
			'|',
			$this->pi_linkTP_keepPIvars('|', array('action' => 'delete', 'confirmed' => 'yes'))
		);
		$linkMarkerArray['###LINK_NO###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array('action' => 'listing')));

		$content = $this->cObj->substituteMarkerArray($tplBase, $baseMarkerArray, '###|###', 1);

		return $this->substituteMarkerArrayNoCached($content, array(), array(), $linkMarkerArray);
	}

	/**
	 * Deletes an address from the database. It doesn't delete the dataset in
	 * real, but it sets the deleted flag like it's done inside TYPO3.
	 * This method has no params, because it currently gets the data from piVars.
	 *
	 * @return bool
	 */
	protected function deleteAddress() {
		if (!in_array((int) $this->piVars['addressid'], array_keys($this->addresses))) {
			return TRUE;
		}

		// Hook to delete an address
		$message = '';

		$hooks = HookFactory::getHooks('Controller/AddressesController', 'deleteAddress');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'deleteAddress')) {
				$message = $hook->deleteAddress((int) $this->piVars['addressid'], $this);
			}
		}

		if ($message) {
			$this->sysMessage = $message;
			$this->sysMessageIsError = TRUE;
			return TRUE;
		}

		$database = $this->getDatabaseConnection();
		$database->exec_UPDATEquery('tt_address', 'uid = ' . (int) $this->piVars['addressid'], array('deleted' => 1));

		unset($this->addresses[(int) $this->piVars['addressid']]);
		unset($this->piVars['confirmed']);

		return $database->sql_error() == '';
	}

	/**
	 * Returns a single input form field.
	 * This is just a switch between the specific methods.
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration for this field
	 * @param string $fieldValue Current value of this field
	 *
	 * @return string Result of the specific field methods (usually a html string)
	 */
	protected function getInputField($fieldName, array $fieldConfig, $fieldValue = '') {
		$content = '';
		switch (strtolower($fieldConfig['type'])) {
			case 'select':
				$content .= $this->getSelectInputField($fieldName, $fieldConfig, $fieldValue);
				break;

			case 'static_info_tables':
				$selected = $fieldValue != '' ? $fieldValue : $fieldConfig['default'];
				$content .= $this->staticInfo->buildStaticInfoSelector(
					$fieldConfig['field'],
					$this->prefixId . '[' . $fieldName . ']',
					$fieldConfig['cssClass'],
					$selected,
					'',
					'',
					'',
					'',
					$fieldConfig['select'],
					$this->getFrontendController()->tmpl->setup['config.']['language']
				);
				break;

			case 'check':
				$content .= $this->getCheckboxInputField($fieldName, $fieldConfig, $fieldValue);
				break;

			case 'single':
			default:
				$content .= $this->getSingleInputField($fieldName, $fieldConfig, $fieldValue);
		}

		/**
		 * Hook for processing the content
		 */
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'getInputField');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'postGetInputField')) {
				$content = $hook->postGetInputField($content, $fieldName, $fieldConfig, $fieldValue, $this);
			}
		}

		return $content;
	}

	/**
	 * Returns a single textfield
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration for this field
	 * @param string $fieldValue Current value of this field
	 *
	 * @return string A single field with type = text
	 */
	protected function getSingleInputField($fieldName, array $fieldConfig, $fieldValue = '') {
		if (isset($fieldConfig['default']) && empty($fieldValue)) {
			$value = $fieldConfig['default'];
		} else {
			$value = \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS(strip_tags($fieldValue));
		}

		$result = '<input type="text" name="' . $this->prefixId . '[' . $fieldName . ']" value="' . $value . '" ';

		if ($fieldConfig['readonly'] == 1) {
			$result .= 'readonly="readonly" disabled="disabled" ';
		}

		if (isset($fieldConfig['class'])) {
			$result .= 'class="' . $fieldConfig['class'] . '" ';
		}

		$result .= '/>';

		return $result;
	}

	/**
	 * Create a selectbox
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration for this field
	 * @param string $fieldValue Current value of this field
	 *
	 * @return string HTML code for a select box with a set of options
	 */
	protected function getSelectInputField($fieldName, array $fieldConfig, $fieldValue = '') {
		$result = '<select name="' . $this->prefixId . '[' . $fieldName . ']">';

		if ($fieldValue != '') {
			$fieldConfig['default'] = $fieldValue;
		}

		// If static items are set
		if (is_array($fieldConfig['values.'])) {
			foreach ($fieldConfig['values.'] as $key => $option) {
				$result .= '<option name="' . $key . '" value="' . $key . '"';
				if ($fieldValue === $key) {
					$result .= ' selected="selected"';
				}
				$result .= '>' . $option . '</option>' . LF;
			}
		} else {
			// Fetch data from database
			$select = $fieldConfig['select'] . $this->cObj->enableFields($fieldConfig['table']);
			$fields = $fieldConfig['label'] . ' AS label,' . $fieldConfig['value'] . ' AS value';

			$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
				$fields,
				$fieldConfig['table'],
				$select,
				'',
				$fieldConfig['orderby']
			);
			foreach ($rows as $row) {
				$result .= '<option name="' . $row['value'] . '" value="' . $row['value'] . '"';
				if ($row['value'] === $fieldConfig['default']) {
					$result .= ' selected="selected"';
				}
				$result .= '>' . $row['label'] . '</option>' . LF;
			}
		}
		$result .= '</select>';

		return $result;
	}

	/**
	 * Returns a checkbox
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration for this field
	 * @param string $fieldValue Current value of this field
	 *
	 * @return string A single checkbox
	 */
	protected function getCheckboxInputField($fieldName, array $fieldConfig, $fieldValue = '') {
		$result = '<input type="checkbox" name="' . $this->prefixId . '[' . $fieldName . ']" id="' . $this->prefixId . '[][' .
			$fieldName . ']" value="1" ';

		if (($fieldConfig['default'] == '1' && $fieldValue != 0) || $fieldValue == 1) {
			$result .= 'checked="checked" ';
		}

		$result .= ' /> ';

		if ($fieldConfig['additionalinfo'] != '') {
			$result .= $fieldConfig['additionalinfo'];
		}

		return $result;
	}

	/**
	 * Reads in the complete configuration for a form, and parses the data
	 * that come from the piVars and checks if this values fit the configuration
	 * for the field.
	 * Errors are stored in internal formError Array. The key will be the name
	 * of the field and the value will be the error message.
	 *
	 * @return bool
	 */
	protected function checkAddressForm() {
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'checkAddressForm');

		$config = $this->conf['formFields.'];
		$result = TRUE;

		// If the address doesn't exsist in session it's valid. In case no delivery
		// address was set.
		foreach ($this->fieldList as $name) {
			$value = trim($this->piVars[$name]);
			$options = $this->conf['formFields.'][$name . '.'];

			if ($options['mandatory'] == 1 && $value == '') {
				$this->setFormError($name, $this->pi_getLL('error_field_mandatory'));
				$result = FALSE;
			}

			$eval = array();
			if (isset($config[$name . '.']['eval']) && $config[$name . '.']['eval'] != '') {
				$eval = explode(',', $config[$name . '.']['eval']);
			}

			foreach ($eval as $method) {
				$method = explode('_', $method);
				switch (strtolower($method[0])) {
					case 'email':
						if (!empty($value) && !\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($value)) {
							$this->setFormError($name, $this->pi_getLL('error_field_email'));
							$result = FALSE;
						}
						break;

					case 'string':
						if (!is_string($value)) {
							$this->setFormError($name, $this->pi_getLL('error_field_string'));
							$result = FALSE;
						}
						break;

					case 'int':
						if (!is_integer($value)) {
							$this->setFormError($name, $this->pi_getLL('error_field_int'));
							$result = FALSE;
						}
						break;

					case 'min':
						if (strlen((string)$value) < (int) $method[1]) {
							$this->setFormError($name, sprintf($this->pi_getLL('error_field_min'), $method[1]));
							$result = FALSE;
						}
						break;

					case 'max':
						if (strlen((string)$value) > (int) $method[1]) {
							$this->setFormError($name, sprintf($this->pi_getLL('error_field_max'), $method[1]));
							$result = FALSE;
						}
						break;

					case 'alpha':
						if (preg_match('/[0-9]/', $value) === 1) {
							$this->setFormError($name, $this->pi_getLL('error_field_alpha'));
							$result = FALSE;
						}
						break;

					default:
						if (!empty($method[0])) {
							$currentMethod = 'validationMethod_' . strtolower($method[0]);
							foreach ($hooks as $hookObj) {
								if (method_exists($hookObj, $currentMethod)) {
									if (!$hookObj->{$currentMethod}($this, $name, $value)) {
										$result = FALSE;
									}
								}
							}
						}
				}
			}
		}

		return $result;
	}

	/**
	 * Save some data from piVars as address into database.
	 *
	 * @param bool $new If this is TRUE, a new address will be created,
	 * 		otherwise it searches for an existing dataset and updates it
	 * @param int $addressType Type of address delivered by piVars
	 *
	 * @return void
	 */
	protected function saveAddressData($new = FALSE, $addressType = 0) {
		// Hooks to process new/changed address
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'saveAddress');
		$database = $this->getDatabaseConnection();
		$newData = array();

		// Set basic data
		if (empty($addressType)) {
			$addressType = 0;
		}

		if ($this->piVars['ismainaddress'] == 'on') {
			$newData['tx_commerce_is_main_address'] = 1;
			// Remove all "is main address" flags from addresses that
			// are assigned to this user
			$database->exec_UPDATEquery(
				'tt_address',
				'pid = ' . $this->conf['addressPid'] . ' AND tx_commerce_fe_user_id=' . $this->user['uid'] .
					' AND tx_commerce_address_type_id=' . $addressType,
				array('tx_commerce_is_main_address' => 0)
			);
		} else {
			$newData['tx_commerce_is_main_address'] = 0;
		}

		$newData['tstamp'] = time();

		foreach ($this->fieldList as $name) {
			$newData[$name] = \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS(strip_tags($this->piVars[$name]));
			if (!$new) {
				$this->addresses[(int) $this->piVars['addressid']][$name] = $newData[$name];
			}
		}

		if ($new) {
			$newData['tx_commerce_fe_user_id'] = $this->user['uid'];
			$newData['tx_commerce_address_type_id'] = $addressType;
			$newData['pid'] = $this->conf['addressPid'];

			foreach ($hooks as $hookObj) {
				if (method_exists($hookObj, 'beforeAddressSave')) {
					$hookObj->beforeAddressSave($newData, $this);
				}
			}

			$database->exec_INSERTquery('tt_address', $newData);
			$newUid = $database->sql_insert_id();

			foreach ($hooks as $hookObj) {
				if (method_exists($hookObj, 'afterAddressSave')) {
					$hookObj->afterAddressSave($newUid, $newData, $this);
				}
			}

			$this->addresses = $this->getAddresses((int) $this->user['uid']);
		} else {
			foreach ($hooks as $hookObj) {
				if (method_exists($hookObj, 'beforeAddressEdit')) {
					$hookObj->beforeAddressEdit((int)$this->piVars['addressid'], $newData, $this);
				}
			}

			$sWhere = 'uid = ' . (int) $this->piVars['addressid'] . ' AND tx_commerce_fe_user_id = ' .
				$this->getFrontendUser()->user['uid'];

			$database->exec_UPDATEquery('tt_address', $sWhere, $newData);

			foreach ($hooks as $hookObj) {
				if (method_exists($hookObj, 'afterAddressEdit')) {
					$hookObj->afterAddressEdit((int)$this->piVars['addressid'], $newData, $this);
				}
			}
		}
	}

	/**
	 * Create a list of array keys where the last character is removed from it.
	 *
	 * @param array $dataArray Array where the keys should be cleaned
	 *
	 * @return array with the cleaned arraykeys or the orginal data if not an array
	 */
	protected function parseFieldList(array $dataArray) {
		$result = array();

		if (!is_array($dataArray)) {
			return $result;
		}

		foreach (array_keys($dataArray) as $key) {
			// remove the trailing '.'
			$result[] = substr($key, 0, -1);
		}

		return $result;
	}

	/**
	 * Get all addresses from the database that are assigned to the current user.
	 *
	 * @param int $userId UID of the user
	 * @param int $addressType Type of addresses to retrieve
	 *
	 * @return array Keys with UIDs and values with complete addresses data
	 */
	public function getAddresses($userId, $addressType = 0) {
		$select = 'tx_commerce_fe_user_id = ' . (int) $userId . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_address');

		if ($addressType > 0) {
			$select .= ' AND tx_commerce_address_type_id=' . (int) $addressType;
		} elseif (isset($this->conf['selectAddressTypes'])) {
			$select .= ' AND tx_commerce_address_type_id IN (' . $this->conf['selectAddressTypes'] . ')';
		} else {
			$this->addresses = array();
			return array();
		}

		$select .= ' AND deleted=0 AND pid=' . $this->conf['addressPid'];

		/**
		 * Hook for adding select statement
		 */
		$hooks = HookFactory::getHooks('Controller/AddressesController', 'getAddresses');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'editSelectStatement')) {
				$select = $hook->editSelectStatement($select, $userId, $addressType, $this);
			}
		}

		$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'tt_address',
			$select,
			'',
			'tx_commerce_is_main_address desc'
		);

		$result = array();
		foreach ($rows as $address) {
			$result[$address['uid']] = \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($address);
		}

		return $result;
	}

	/**
	 * Returns if there are any form field errors
	 *
	 * @return bool
	 */
	public function formErrors() {
		return count($this->formError);
	}

	/**
	 * Returns if given $fieldName was not submitted correctly
	 *
	 * @param string $fieldName The name of the field
	 *
	 * @return string error message or empty string if no error
	 */
	public function getFormError($fieldName) {
		return $this->formError[$fieldName];
	}

	/**
	 * Set an error for a field
	 *
	 * @param string $fieldName The name of the field
	 * @param string $errorMsg The error message for the field
	 *
	 * @return void
	 */
	public function setFormError($fieldName, $errorMsg) {
		$this->formError[$fieldName] = $errorMsg;
	}
}
