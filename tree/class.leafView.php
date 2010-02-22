<?php
/**
 * Implements the view of the leaf
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.langbase.php');

class leafView extends langbase {
	
	protected $leafIndex 		= false;
	protected $parentIndices;
	
	protected $table;	
	
	//Iconpath and Iconname
	protected $iconPath = '../typo3conf/ext/commerce/res/icons/table/'; ###ALSO MAKE THIS TO BE FILLED BY EXTENDING CLASSES###
	protected $iconName; ###IS THIS NECESSARY?###
	protected $BACK_PATH = '../../../../typo3/';	//Back Path ###MAKE THIS THAT OTHER CALSSES HAVE TO FILL IT####
	protected $domIdPrefix = 'txcommerceLeaf'; 		//Prefix for DOM Id ###WHAT ABOUT THE DOM PREFIX - make this by extending class###
	protected $titleAttrib = 'title'; 				//HTML title attribute 
	protected $bank; 								//Item UID of the Mount for this View
	protected $treeName;							//Name of the Tree
	protected $rootIconName = 'commerce_globus.gif'; ###MAKE THIS TO BE FILLED BY EXTENDING VIEW###
	protected $cmd;						
	protected $noClickmenu;							//Should clickmenu be enabled
	protected $noRootOnclick = false;				//Should the root item have a title-onclick?
	protected $noOnclick = false;					//hould the otem in general have a title-onclick?
	protected $realValues = false;					// use real values for leafs that otherwise just have "edit"
	
	//Internal
	protected $icon;
	protected $iconGenerated = false;
	
	/**
	 * Initialises the variables iconPath and BACK_PATH
	 * @return {void}
	 */
	
	public function __construct(){
		
		if (t3lib_div::int_from_ver(TYPO3_version) >= '4002007') {
		 	$rootPathT3 = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		}else{
			// Code TYPO3 Site Path manually, backport from TYPO3 4.2.7 svn
			$rootPathT3 = substr(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')));
		}
		
		

		// If we don't have any data, set /
		if (empty($rootPathT3)){
			$rootPathT3 = '/';
		}
		$this->iconPath = $rootPathT3.TYPO3_mainDir.PATH_txcommerce_icon_tree_rel;
		$this->BACK_PATH = $rootPathT3.TYPO3_mainDir;
		
	}
	/**
	 * Sets the Leaf Index
	 * 
	 * @param $index {int}	Leaf Index
	 * @return {void}
	 */
	public function setLeafIndex($index) {
		if(!is_numeric($index)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setLeafIndex (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->leafIndex = $index;
	}
	
	/**
	 * Sets the parent indices
	 * 
	 * @return {void}
	 * @param $indices {array}[optional]	Array with the Parent Indices
	 */
	public function setParentIndices($indices = array()) {
		if(!is_array($indices)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setParentIndices (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->parentIndices = $indices;
	}
	
	/**
	 * Sets the bank
	 * 
	 * @param {int} $bank - Category UID of the Mount (aka Bank)
	 * @return {void}
	 */
	function setBank($bank) {
		if(!is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setBank (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->bank = $bank;
	}
	
	/**
	 * Sets the Tree Name of the Parent Tree
	 * 
	 * @return {void}
	 * @param $name {string} - Name of the tree
	 */
	function setTreeName($name) {
		if(!is_string($name)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setTreeName (leafview) gets passed wrong-cast parameters. Should be string but is not.', COMMERCE_EXTkey, 2);	
		}
		$this->treeName = $name;
	}
	
	/**
	 * Sets if the clickmenu should be enabled for this leafview
	 * 
	 * @return {void}
	 * @param $flag {boolean}[optional]	Flag
	 */
	public function noClickmenu($flag = true) {
		$this->noClickmenu = (bool)$flag;
	}
	
	/**
	 * Sets if the root onlick should be enabled for this leafview
	 * 
	 * @return {void}
	 * @param $flag {boolean}[optional]	Flag
	 */
	public function noRootOnclick($flag = true) {
		$this->noRootOnclick = (bool)$flag;
	}
	
	/**
	 * Sets the noClick for the title
	 * 
	 * @return {void}
	 * @param $flag {boolean}
	 */
	public function noOnclick($flag = true) {
		$this->noOnclick = $flag;
	}
	
	/**
	 * Will set the real values to the views
	 * for products and articles, instead of "edit"
	 * 
	 * @return void
	 */
	public function substituteRealValues() {
		$this->realValues = true;
	}
	
	/**
	 * Get icon for the row.
	 * If $this->iconPath and $this->iconName is set, try to get icon based on those values.
	 *
	 * @param	array		Item row.
	 * @return	string		Image tag.
	 */
	function getIcon($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getIcon (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		if ($this->iconPath && $this->iconName) {
			$icon = '<img'.t3lib_iconWorks::skinImg('',$this->iconPath.$this->iconName,'width="18" height="16"').' alt=""'.($this->showDefaultTitleAttribute ? ' title="UID: '.$row['uid'].'"':'').' />';
	
		} else {
			
			$icon = t3lib_iconWorks::getIconImage($this->table,$row,$this->BACK_PATH,'align="top" class="c-recIcon"');
		}

		return $this->wrapIcon($icon, $row);
	}
	
	/**
	 * Get the icon for the root
	 * $this->iconPath and $this->rootIconName have to be set
	 * 
	 * @return string Image tag 
	 */
	function getRootIcon($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRootIcon (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}

		$icon = '<img'.t3lib_iconWorks::skinImg($this->iconPath, $this->rootIconName,'width="18" height="16"').' title="Root" alt="" />';
	
		return $this->wrapIcon($icon, $row);
	}
	
	
	/**
	 * Wraps the Icon in a <span>
	 * 
	 * @return {string}	HTML Code
	 */
	function wrapIcon($icon, $row, $addParams = '') {
		if(!is_array($row) || !is_string($addParams)) {
			if (TYPO3_DLOG) t3lib_div::devLog('wrapIcon (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		$icon = $this->addTagAttributes($icon,($this->titleAttrib ? $this->titleAttrib.'="'.$this->getTitleAttrib($row).'"' : ''));
		
		//Wrap the Context Menu on the Icon if it is allowed
		if(isset($GLOBALS['TBE_TEMPLATE']) && !$this->noClickmenu) {
			$icon = '<a href="#">'.$GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($icon,$this->table,$row['uid'],0, $addParams).'</a>';
		}
		return $icon;
	}
	
	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title, $row, $bank = 0)	{
		if(!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('wrapTitle (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);		
			return '';
		}
		
		$res = '';
		
		//Max. size for Title of 30
		$title = ('' != $title) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');
		
		$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$row['uid'].'_'.$bank.'\',\'\');';

		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ? $title : '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';

		return $res;
	}
	
	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param	{object} $row - Array with the ID Information
	 * @return	{string}
	 */
	function getJumpToParam($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getJumpToParam (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		$res = 'id='.$row['uid'];
		return $res;
	}
	
	/**
	 * Adds attributes to image tag.
	 *
	 * @param	string		Icon image tag
	 * @param	string		Attributes to add, eg. ' border="0"'
	 * @return	string		Image tag, modified with $attr attributes added.
	 */
	function addTagAttributes($icon,$attr)	{
		if(!is_string($icon) || !is_string($attr)) {
			if (TYPO3_DLOG) t3lib_div::devLog('addTagAttributes (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		return str_replace(' />', '', $icon).' '.$attr.' />';
	}
	
	/**
	 * Returns the value for the image "title" attribute
	 *
	 * @param	array		The input row array (where the key "title" is used for the title)
	 * @return	string		The attribute value (is htmlspecialchared() already)
	 * @see wrapIcon()
	 */
	function getTitleAttrib($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getTitleAttrib (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		return 'id='.$row['uid'];
	}
	
	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @param 	boolean		The Element is a Bank if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon(&$row, $isLast, $isExpanded,$isBank = false, $hasChildren = false)	{
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('PMicon (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';
		}
		
		$PM   = $hasChildren ? ($isExpanded ? 'minus' : 'plus') : 'join';
		$BTM  = ($isLast) ? 'bottom' : '';
		$BTM  = ($isBank) ? 'only' : $BTM;		//If the current row is a bank, display only the plus/minus
		$icon = '<img'.t3lib_iconWorks::skinImg($this->BACK_PATH,'gfx/ol/'.$PM.$BTM.'.gif','width="18" height="16"').' alt="" />';

		if ($hasChildren) {
			//Calculate the command
			$indexFirst = (0 >= count($this->parentIndices)) ? $this->leafIndex : $this->parentIndices[0];
			
			$cmd = array($this->treeName, $indexFirst, $this->bank, ($isExpanded ? 0 : 1));
			
			//Add the parentIndices to the Command (also its own index since it has not been added if we HAVE parent indices
			if(0 < count($this->parentIndices)) {
				$l = count($this->parentIndices);
				
				//Add parent indices - first parent Index is already in the command
				for($i = 1; $i < $l; $i ++) {
					$cmd[] = $this->parentIndices[$i];
				}
				
				//Add its own index at the very end
				$cmd[] = $this->leafIndex;
			}
			
			$cmd[] 	= $row['uid'].'|'.$row['item_parent'];		//Append the row UID | Parent Item under which this row stands
			$cmd[3] = ($isExpanded ? 0 : 1);	//Overwrite the Flag for expanded
		
			//Make the string-command
			$cmd = implode('_', $cmd);
			
			$icon = $this->PMiconATagWrap($icon, $cmd, !$isExpanded);
		}
		return $icon;
	}
	
	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		if(!is_string($icon) || !is_string($cmd)) {
			if (TYPO3_DLOG) t3lib_div::devLog('PMiconATagWrap (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		// activate dynamic ajax-based tree
		$js = htmlspecialchars('Tree.load(\''.$cmd.'\', '.intval($isExpand).', this);');
		return '<a class="pm" onclick="'.$js.'">'.$icon.'</a>';
	}
}
?>
