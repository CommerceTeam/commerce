<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Schmitt <is@marketing-factory.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/




/**
 * TXclass for fix TYPO3 Core Issue 10331
 * @see http://bugs.typo3.org/view.php?id=10331 
 *
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>

 * @see t3lib/class.t3lib_parsehtml_proc.php
 */
class ux_t3lib_parsehtml_proc extends t3lib_parsehtml_proc {

	/**
	 * Transformation handler: 'ts_links' / direction: "rte"
	 * Converting <link tags> to <A>-tags
	 *
	 * @param	string		Content input
	 * @return	string		Content output
	 * @see TS_links_rte()
	 */
	function TS_links_rte($value)	{
		$value = $this->TS_AtagToAbs($value);

			// Split content by the TYPO3 pseudo tag "<link>":
		$blockSplit = $this->splitIntoBlock('link',$value,1);
		foreach($blockSplit as $k => $v)	{
			$error = '';
			if ($k%2)	{	// block:
				$tagCode = t3lib_div::unQuoteFilenames(trim(substr($this->getFirstTag($v),0,-1)),true);
				$link_param = $tagCode[1];
				$href = '';
				$siteUrl = $this->siteUrl();
					// Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
				if(strstr($link_param,'@'))	{		// mailadr
					$href = 'mailto:'.eregi_replace('^mailto:','',$link_param);
				} elseif (substr($link_param,0,1)=='#') {	// check if anchor
					$href = $siteUrl.$link_param;
				} else {
					$fileChar=intval(strpos($link_param, '/'));
					$urlChar=intval(strpos($link_param, '.'));

						// Detects if a file is found in site-root OR is a simulateStaticDocument.
					list($rootFileDat) = explode('?',$link_param);
					$rFD_fI = pathinfo($rootFileDat);
					if (trim($rootFileDat) && !strstr($link_param,'/') && (@is_file(PATH_site.$rootFileDat) || t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
						$href = $siteUrl.$link_param;
					} elseif($urlChar && (strstr($link_param,'//') || !$fileChar || $urlChar<$fileChar))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
						if (!ereg('^[a-z]*://',trim(strtolower($link_param))))	{$scheme='http://';} else {$scheme='';}
						$href = $scheme.$link_param;
					} elseif($fileChar)	{	// file (internal)
						$href = $siteUrl.$link_param;
					} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
						$link_params_parts = explode('#',$link_param);
						$idPart = trim($link_params_parts[0]);		// Link-data del
						if (!strcmp($idPart,''))	{ $idPart=$this->recPid; }	// If no id or alias is given, set it to class record pid

// FIXME commented because useless - what is it for?
//						if ($link_params_parts[1] && !$sectionMark)	{
//							$sectionMark = '#'.trim($link_params_parts[1]);
//						}

							// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
						$pairParts = t3lib_div::trimExplode(',',$idPart);
						if (count($pairParts)>1)	{
							$idPart = $pairParts[0];
							// Type ? future support for?
						}
							// Checking if the id-parameter is an alias.
						if (!t3lib_div::testInt($idPart))	{
							list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$idPart);
							$idPart = intval($idPartR['uid']);
						}
						$page = t3lib_BEfunc::getRecord('pages', $idPart);
						if (is_array($page))	{	// Page must exist...
							$href = $siteUrl.'?id='.$link_param;
						} elseif (stripos($link_param, 'record:') === 0 || isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][array_shift(explode(':', $link_param))])) {
							// linkHandler - allowing links to start with registerd linkHandler e.g.. "record:"
							$href = $link_param;
						} else {
							#$href = '';
							$href = $siteUrl.'?id='.$link_param;
							$error = 'No page found: '.$idPart;
						}
					}
				}

				// Setting the A-tag:
				$bTag = '<a href="'.htmlspecialchars($href).'"'.
							($tagCode[2]&&$tagCode[2]!='-' ? ' target="'.htmlspecialchars($tagCode[2]).'"' : '').
							($tagCode[3]&&$tagCode[3]!='-' ? ' class="'.htmlspecialchars($tagCode[3]).'"' : '').
							($tagCode[4] ? ' title="'.htmlspecialchars($tagCode[4]).'"' : '').
							($error ? ' rteerror="'.htmlspecialchars($error).'" style="background-color: yellow; border:2px red solid; color: black;"' : '').	// Should be OK to add the style; the transformation back to databsae will remove it...
							'>';
				$eTag = '</a>';
				$blockSplit[$k] = $bTag.$this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}
		}

			// Return content:
		return implode('',$blockSplit);
	}
	
	
	
	
	

	
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/class.ux_t3lib_parsehtml_proc'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/class.ux_t3lib_parsehtml_proc']);
}
?>