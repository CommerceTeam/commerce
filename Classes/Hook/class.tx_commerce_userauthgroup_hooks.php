<?php
/**
 * Implements the hooks for the class typo3/userAuthGroup.php
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class tx_commerce_userauthgroup_hooks {
	
		/**
		 * Adds the CategoryMounts to the User Object as a list
		 * @return {void}
		 * @param $params {array}		Params delivered from the hook - in our case, it's empty!
		 * @param $ref {object}			Reference to the user object
		 * 
		 * @deprecated
		 */
		/*function fetchGroups_postProcessing($params, &$ref) {
			//Add to the dataList
			//$ref->dataLists['categorymount_list'] = array();
			
			//Walk every group and get the categorymounts and add them 
			$groups 	= $ref->userGroups;
			$keys		= array_keys($groups);
			$group  	= null;
			$mountlist 	= '';
			
			for($i = 0, $l = count($keys); $i < $l; $i ++) {
				$mountlist .= $groups[$keys[$i]]['tx_commerce_mountpoints'];
			}
			
			$mountlist = t3lib_div::uniqueList($mountlist);
			
			//Store the mountlist in the user object
			$ref->groupData['categorymount_list'] = $mountlist;
		}*/
		
}	
?>
