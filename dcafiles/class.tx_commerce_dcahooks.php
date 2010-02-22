<?php
/**
 * Created on 05.11.2008
 * 
 * Implements the DCA Hooks for Commerce
 * Changes the DCA for articles to enable the necessary mktime() function call
 * 
 * @author Erik Frister <efrister@web-factory.de>
 */
class tx_commerce_dcahooks {
	
	/**
	 * Gets called when the DCA is instantiated
	 * 
	 * @return void
	 * @param $currentDCA 	array	current DCA configuration
	 * @param $table 		string	the dca table
	 */
	function alterDCA_onLoad(&$currentDCA, $table) {
		
		// make sure that we only alter the articles table
		if('tx_commerce_articles' == $table) {
			
			// prepare the change
			$changeIndex = 1;	// which DCA Index
			$modIndex 	 = 1;	// which modification
			$modPos		 = 5;	// which field-config position
			
			$time = mktime(0,0,0,date('m')-1,date('d'),date('Y'));
			
			// perform the change
			$currentDCA[$modIndex]['modifications'][$changeIndex]['field_config'][$modPos]['config']['range']['lower'] = $time;
		}
	}
}
?>
