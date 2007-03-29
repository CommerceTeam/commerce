<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Ingo Schmitt (is@marketing-factory.de)
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
 * Extension for t3lib_tceforms
 * for extending the none type. Should only be used in TYPO3 below 4.0 
 * as feature was introduced in 4.0.0
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @see TYPO3 4.0.0 rc1, mostly copied from there
 * 
 * $Id: class.ux_t3lib_tceforms.php 318 2006-08-03 16:09:34Z franz $
 */
 
 class ux_t3lib_TCEforms extends t3lib_TCEforms {
 
 	/**
	 * Generation of TCEform elements of the type "none"
	 * This will render a non-editable display of the content of the field.
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeNone($table,$field,$row,&$PA)	{
			// Init:
		$config = $PA['fieldConf']['config'];
		$itemValue = $PA['itemFormElValue'];

		return $this->getSingleField_typeNone_render($config,$itemValue);
	}

	/**
	 * HTML rendering of a value which is not editable.
	 *
	 * @param	array		Configuration for the display
	 * @param	string		The value to display
	 * @return	string		The HTML code for the display
	 * @see getSingleField_typeNone();
	 */
	function getSingleField_typeNone_render($config,$itemValue)	{

				// is colorScheme[0] the right value?
		$divStyle = 'border:solid 1px '.t3lib_div::modifyHTMLColorAll($this->colorScheme[0],-30).';'.$this->defStyle.$this->formElStyle('none').' background-color: '.$this->colorScheme[0].'; padding-left:1px;color:#555;';

		if ($config['format'])	{
			$itemValue = $this->formatValue($config, $itemValue);
		}

		$rows = intval($config['rows']);
		if ($rows > 1) {
			if(!$config['pass_content']) {
				$itemValue = nl2br(htmlspecialchars($itemValue));
			}
				// like textarea
			$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = $rows = t3lib_div::intInRange($rows, 1, 20);
				if (strlen($itemValue)>$this->charsPerRow*2)	{
					$cols = $this->maxTextareaWidth;
					$rows = t3lib_div::intInRange(round(strlen($itemValue)/$this->charsPerRow),count(explode(chr(10),$itemValue)),20);
					if ($rows<$origRows)	$rows=$origRows;
				}
			}

			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);
				// hardcoded: 12 is the height of the font
			$height=$rows*12;

			$item='
				<div style="'.htmlspecialchars($divStyle.' overflow:auto; height:'.$height.'px; width:'.$width.'px;').'" class="'.htmlspecialchars($this->formElClass('none')).'">'.
				$itemValue.
				'</div>';
		} else {
			if(!$config['pass_content']) {
				$itemValue = htmlspecialchars($itemValue);
			}

			$cols = $config['cols']?$config['cols']:($config['size']?$config['size']:$this->maxInputWidth);
			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);

				// overflow:auto crashes mozilla here. Title tag is usefull when text is longer than the div box (overflow:hidden).
			$item = '
				<div style="'.htmlspecialchars($divStyle.' overflow:hidden; width:'.$width.'px;').'" class="'.htmlspecialchars($this->formElClass('none')).'" title="'.$itemValue.'">'.
				'<span class="nobr">'.(strcmp($itemValue,'')?$itemValue:'&nbsp;').'</span>'.
				'</div>';
		}

		return $item;
	}
	
	
	/************************************************************
	 *
	 * Field content processing
	 *
	 ************************************************************/

	/**
	 * Format field content of various types if $config['format'] is set to date, filesize, ..., user
	 * This is primarily for the field type none but can be used for user field types for example
	 *
	 * @param	array		Configuration for the display
	 * @param	string		The value to display
	 * @return	string		Formatted Field content
	 */
	function formatValue ($config, $itemValue)	{
		$format = trim($config['format']);
		switch($format)	{
			case 'date':
				$option = trim($config['format.']['option']);
				if ($option)	{
					if ($config['format.']['strftime'])	{
						$value = strftime($option,$itemValue);
					} else {
						$value = date($option,$itemValue);
					}
				} else {
					$value = date('d-m-Y',$itemValue);
				}
				if ($config['format.']['appendAge'])	{
					$value .= ' ('.t3lib_BEfunc::calcAge((time()-$itemValue), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')).')';
				}
				$itemValue = $value;
				break;
			case 'datetime':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i d-m-Y',$itemValue);
				break;
			case 'time':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i',$itemValue);
				break;
			case 'timesec':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i:s',$itemValue);
				break;
			case 'year':	// compatibility with "eval" (type "input")
				$itemValue = date('Y',$itemValue);
				break;
			case 'int':
				$baseArr = array('dec'=>'d','hex'=>'x','HEX'=>'X','oct'=>'o','bin'=>'b');
				$base = trim($config['format.']['base']);
				$format = $baseArr[$base] ? $baseArr[$base] : 'd';
				$itemValue = sprintf('%'.$format,$itemValue);
				break;
			case 'float':
				$precision = t3lib_div::intInRange($config['format.']['precision'],1,10,2);
				$itemValue = sprintf('%.'.$precision.'f',$itemValue);
				break;
			case 'number':
				$format = trim($config['format.']['option']);
				$itemValue = sprintf('%'.$format,$itemValue);
				break;
			case 'md5':
				$itemValue = md5($itemValue);
				break;
			case 'filesize':
				$value = t3lib_div::formatSize(intval($itemValue));
				if ($config['format.']['appendByteSize'])	{
					$value .= ' ('.$itemValue.')';
				}
				$itemValue = $value;
				break;
			case 'user':
				$func = trim($config['format.']['userFunc']);
				if ($func)	{
					$params = array(
						'value' => $itemValue,
						'args' => $config['format.']['userFunc'],
						'config' => $config,
						'pObj' => &$this
					);
					$itemValue = t3lib_div::callUserFunction($func,$params,$this);
				}
				break;
			default:
			break;
		}

		return $itemValue;
	}
 		
 	
 }
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/class.ux_t3lib_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/class.ux_t3lib_tceforms.php']);
}
 ?>