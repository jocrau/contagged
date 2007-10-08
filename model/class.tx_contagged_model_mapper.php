<?php
/***************************************************************
	*  Copyright notice
	*
	*  (c) 2007 Jochen Rau <j.rau@web.de>
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
	*
	*  This script is distributed in the hope that it will be useful,
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	*  GNU General Public License for more details.
	*
	*  This copyright notice MUST APPEAR in all copies of the script!
	***************************************************************/

/**
	* The model of contagged.
	* 
	* @author	Jochen Rau <j.rau@web.de>
	* @package	TYPO3
	* @subpackage	tx_contagged_model_mapper
	*/
class tx_contagged_model_mapper {
	var $conf; // the TypoScript configuration array
	var $cObj;
	var $controller;
	
	function tx_contagged_model_mapper($controller) {
		$this->controller = $controller;
		$this->conf = $controller->conf;
		$this->cObj = $controller->cObj;
	}

	/**
		* Build an array of the entries in the table "tx_contagged_terms"
		*
		* @return	An array with the data of the table "tx_contagged_terms"
		*/
	function getDataArray($result,$dataSource) {
		$dataArray = array();
		$terms = array();

		$dataSourceConfigArray = $this->conf['dataSources.'][$dataSource . '.'];
		$sourceName = $dataSourceConfigArray['sourceName'];

		// add additional fields configured in the mapping configuration of the data source
		$fieldsToMapfromTS = t3lib_div::trimExplode(',', $this->conf['fieldsToMap'], 1);
		foreach ($dataSourceConfigArray['mapping.'] as $fieldToMap => $value) {
				$fieldsToMapArray[] = substr($fieldToMap,0,-1);
		}
		foreach ($fieldsToMapfromTS as $key => $fieldToMap) {
			if ( !t3lib_div::inArray($fieldsToMapArray,$fieldToMap) ) {
				$fieldsToMapArray[] = $fieldToMap;
			}
		}
		
		// iterate through all data from the datasource
		foreach ($result as $row) {
			$secureFields = $this->conf['types.'][$row['term_type'].'.']['termIsRegEx']>0 ? $this->conf['types.'][$row['term_type'].'.']['secureFields'] : $this->conf['secureFields'];
			if ($dataSourceConfigArray['mapping.']['uid.']['field']) {
				$termUid = $row[$dataSourceConfigArray['mapping.']['uid.']['field']];				
			}
			$termMain = $dataSourceConfigArray['mapping.']['term_main.']['field'] ? $dataSourceConfigArray['mapping.']['term_main.']['field'] : '';
			$termReplace = $dataSourceConfigArray['mapping.']['term_replace.']['field'] ? $dataSourceConfigArray['mapping.']['term_replace.']['field'] : '';
			$term = $row[$termReplace] ? $row[$termReplace] : $row[$termMain];
			$dataArray[$termUid] = array();
			$dataArray[$termUid]['term'] = $term;
			foreach ( $fieldsToMapArray as $field) {
				$value = $dataSourceConfigArray['mapping.'][$field.'.'];
				if ( $value['value'] ) {
					$dataArray[$termUid][$field] = $value['value'];
				} elseif ( $value['field'] ) {
					$dataArray[$termUid][$field] = t3lib_div::inList($secureFields,$field) ? htmlspecialchars($row[$value['field']]) : $row[$value['field']];
				} else {
					$dataArray[$termUid][$field] = NULL;
				}
				if ( $value['stdWrap.'] ) {
					$dataArray[$termUid][$field] = $this->cObj->stdWrap($dataArray[$termUid][$field],$value['stdWrap.']);
				}
				$GLOBALS['TSFE']->register['contagged_'.$field] = $dataArray[$termUid][$field];					
				if ( !t3lib_div::inArray($fieldsToMapArray,$field) ) {
					unset( $dataArray[$termUid][$field]);
				}
			}
			// TODO sort the array by descending length of value string; in combination with the htmlparser this will prevend nesting
			// TODO $desc_long = preg_replace('/(\015\012)|(\015)|(\012)/ui','<br />',$row['desc_long']);
			
			// post processing
			$dataArray[$termUid]['term_alt'] = t3lib_div::trimExplode(chr(10),$row['term_alt'],$onlyNonEmptyValues=1);
			$dataArray[$termUid]['desc_long'] = $this->cObj->parseFunc($dataArray[$termUid]['desc_long'],$conf='',$ref='< lib.parseFunc_RTE');
			// TODO: hook "mappingPostProcessing"
		}

		return $dataArray;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_mapper.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_mapper.php']);
}
?>