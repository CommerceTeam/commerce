<?php
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_category.php'); 
/**
 * Holds the TCE Functions
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
class tx_commerce_tceFunc {
	
	/**
	 * This will render a selector box element for selecting elements of (category) trees.
	 * Depending on the tree it display full trees or root elements only
	 *
	 * @param	array		$PA An array with additional configuration options.
	 * @param	object		$fobj TCEForms object reference
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_selectCategories($PA, &$fObj) {
		
		global $TYPO3_CONF_VARS, $TCA, $LANG;
		
		
		$this->tceforms = &$PA['pObj'];
		
		$table 	= $PA['table'];
		$field 	= $PA['field'];
		$row 	= $PA['row'];
		$config = $PA['fieldConf']['config'];
		
		$disabled = '';
		if($this->tceforms->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}
		

		// TODO it seems TCE has a bug and do not work correctly with '1'
		$config['maxitems'] = ($config['maxitems']==2) ? 1 : $config['maxitems'];
		
		//read the permissions we are restricting the tree to, depending on the table
		$perms = 'show';
		
		switch($table) {
			case 'tx_commerce_categories':
				$perms = 'new';
				break;
			
			case 'tx_commerce_products':
				$perms = 'editcontent';
				break;
				
			case 'tt_content':
			case 'be_groups':
			case 'be_users':
				$perms = 'show';
				break;
		}
		
		//Include the tree and the renderer
		require_once(PATH_txcommerce.'treelib/class.tx_commerce_categorytree.php');
		$browseTrees = t3lib_div::makeInstance('tx_commerce_categorytree');
		$browseTrees->noClickmenu();	//disabled clickmenu
		$browseTrees->setMinCategoryPerms($perms); //set the minimum permissions
		
		if($config['allowProducts']) {
			$browseTrees->setBare(false);
		}
		
		/**
		 * Disallows clicks on certain leafs
		 * Values is a comma-separated list of leaf names (e.g. tx_commerce_categories)
		 */
		$browseTrees->disallowClick($config['disallowClick']);
		
		$browseTrees->init();
		
		require_once(PATH_txcommerce.'treelib/class.tx_commerce_treelib_tceforms.php');
		$renderBrowseTrees = t3lib_div::makeInstance('tx_commerce_treelib_tceforms');
		$renderBrowseTrees->init ($PA, $fObj);
		$renderBrowseTrees->setIFrameTreeBrowserScript($this->tceforms->backPath.PATH_txcommerce_rel.'mod_treebrowser/index.php');

		##WHEN ARE WE EVER ALREADY IN THE IFRAME? AND WHEN DO WE EVERY RENDER A DIV? RENDERING IN THE DIV WOULD BRAKE TREE FUNCTIONALITY BECAUSE JS WOULD NOT WORK ANYMORE###
		//Render the tree
		$renderBrowseTrees->renderBrowsableMountTrees($browseTrees);
		
		if (!$disabled) {
			if ($renderBrowseTrees->isIFrameContentRendering()) {

				// just the trees are needed - we're inside of an iframe!
				return $renderBrowseTrees->getTreeContent();

			} elseif ($renderBrowseTrees->isIFrameRendering()) {
				// If we want to display a browseable tree, we need to run the tree in an iframe element
				// In the logic of tceforms the iframe is displayed in the "thumbnails" position
				// In consequence this means that the current function is both responsible for displaying the iframe
				// and displaying the tree. It will be called twice then. Once from alt_doc.php and from dam/mod_treebrowser/index.php

				// Within this if-condition the iframe is written
				// The source of the iframe is dam/mod_treebrowser/index.php which will be called with the current _GET variables
				// In the configuration of the TCA treeViewBrowseable is set to TRUE. The value 'iframeContent' for treeViewBrowseable will
				// be set in dam/mod_treebrowser/index.php as internal configuration logic

				$thumbnails = $renderBrowseTrees->renderIFrame();

			} else {
					// tree frame <div>
				$thumbnails = $renderBrowseTrees->renderDivBox();
			}
		}

		// get selected processed items - depending on the table we want to insert into (tx_commerce_products, tx_commerce_categories, be_users)
		// if row['uid'] is defined and is an integer we do display an existing record
		// otherwhise it's a new record, so get default values
		$itemArray = array();
		
		if (intval($row['uid']) > 0){
			// existing Record
			switch($table) {
				case 'tx_commerce_categories':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreePCategory($browseTrees, $row['uid']);
					break;
				
				case 'tx_commerce_products':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeProduct($browseTrees, $row['uid']);
					break;
					
				case 'be_users':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTree($browseTrees, $row['uid']);
					break;
					
				case 'be_groups':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeGroups($browseTrees, $row['uid']);
					break;
					
				case 'tt_content':
					// Perform modification of the selected items array:
					$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);		
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeCategory($browseTrees, $itemArray[0]);
					break;
			}
		}else{
			// New record
			$defVals= t3lib_div::_GP('defVals');
			switch($table) {
				
				case 'tx_commerce_categories':
						$cat = t3lib_div::makeInstance('tx_commerce_category');
						$cat->init($defVals['tx_commerce_categories']['parent_category']);
						$cat->load_data();
						$itemArray = array($cat->getUid().'|'.$cat->get_title()); 
					break;
				
				case 'tx_commerce_products':
					$cat = t3lib_div::makeInstance('tx_commerce_category');
					$cat->init($defVals['tx_commerce_products']['categories']);
					$cat->load_data();
					$itemArray = array($cat->getUid().'|'.$cat->get_title()); 
				
					break;
					
				
			}
		}
		
		//
		// process selected values
		//

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->tceforms->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ '.$this->tceforms->getLL('l_noMatchingValue').' ]';
		$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);

			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode(',', $PA['fieldTSConfig']['removeItems'], true);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			if (in_array($tvP[0], $removeItems) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'])	{
				$tvP[1] = rawurlencode($nMV_label);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$tvP[0]])) {
				$tvP[1] = rawurlencode($this->tceforms->sL($PA['fieldTSConfig']['altLabels.'][$tvP[0]]));
			}
			$itemArray[$tk] = implode('|', $tvP);
		}

		//
		// Rendering and output
		//

		$minitems = t3lib_div::intInRange($config['minitems'], 0);
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems)	$maxitems = 100000;

		$this->tceforms->requiredElements[$PA['itemFormElName']] = array($minitems, $maxitems, 'imgName' => $table.'_'.$row['uid'].'_'.$field);



		$item = '';
		$item .= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'"'.$disabled.' />';

		$params = array(
			'size' => $config['size'],
			'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
			'style' => ' style="width:200px;"',
			'dontShowMoveIcons' => ($maxitems<=1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->tceforms->getLL('l_selected').':<br />',
				'items' => ($disabled ? '': $this->tceforms->getLL('l_items').':<br />')
			),
			'noBrowser' => true,
			'readOnly' => $disabled,
			'thumbnails' => $thumbnails
		);
	
		$item .= $this->tceforms->dbFileIcons($PA['itemFormElName'], $config['internal_type'], $config['allowed'], $itemArray, '', $params, $PA['onFocus']);


			// Wizards:
		if (!$disabled) {
			$specConf = $this->tceforms->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
			$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
			$item = $this->tceforms->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf);
		}

		return $item;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_tcefunc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_tcefunc.php']);
}
?>
