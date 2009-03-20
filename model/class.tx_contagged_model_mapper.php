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
	 * Build an array of the entries in the specified table
	 *
	 * @param	[type]		$result: ...
	 * @param	[type]		$dataSource: ...
	 * @return	An		array with the data of the table
	 */
	function getDataArray($result,$dataSource) {
		$dataArray = array();

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
			$termMain = $dataSourceConfigArray['mapping.']['term_main.']['field'] ? $dataSourceConfigArray['mapping.']['term_main.']['field'] : '';
			$termReplace = $dataSourceConfigArray['mapping.']['term_replace.']['field'] ? $dataSourceConfigArray['mapping.']['term_replace.']['field'] : '';
			$term = $row[$termReplace] ? $row[$termReplace] : $row[$termMain];
			$mappedDataArray = array();
			$mappedDataArray['term'] = $term;
			$mappedDataArray['sourceName'] = $sourceName;
			foreach ( $fieldsToMapArray as $field) {
				$value = $dataSourceConfigArray['mapping.'][$field.'.'];
				if ( $value['value'] ) {
					$mappedDataArray[$field] = $value['value'];
				} elseif ( $value['field'] ) {
					$mappedDataArray[$field] = $row[$value['field']];
				} else {
					$mappedDataArray[$field] = NULL;
				}
				if ( $value['stdWrap.'] ) {
					$mappedDataArray[$field] = $this->cObj->stdWrap($mappedDataArray[$field],$value['stdWrap.']);
				}
				$GLOBALS['TSFE']->register['contagged_'.$field] = $mappedDataArray[$field];
			}
			// TODO $desc_long = preg_replace('/(\015\012)|(\015)|(\012)/ui','<br />',$row['desc_long']);
			$typeConfigArray = $this->conf['types.'][$mappedDataArray['term_type'].'.'];

			// post processing
			$mappedDataArray['term_alt'] = t3lib_div::trimExplode(chr(10),$row['term_alt'],1);
			$mappedDataArray['desc_long'] = $this->cObj->parseFunc($mappedDataArray['desc_long'],$conf='',$ref='< lib.parseFunc_RTE');
			$mappedDataArray['storagePids'] = $this->getStoragePidsArray($typeConfigArray);
						
			if ($typeConfigArray['listPages']) {
				$mappedDataArray['listPages'] = t3lib_div::trimExplode(',',$typeConfigArray['listPages'],1);
			} else {
				$mappedDataArray['listPages'] = t3lib_div::trimExplode(',',$this->conf['listPages'],1);
			}

			$secureFields = $typeConfigArray['termIsRegEx']>0 ? $this->conf['types.'][$row['term_type'].'.']['secureFields'] : $this->conf['secureFields'];
			foreach ($mappedDataArray as $field => $fieldContent) {
				if ($fieldContent) {
					if ( is_array($fieldContent) ) {
						foreach ($fieldContent as $termAltKey => $innerContent) {
							if ( t3lib_div::inList($secureFields,$field) ) {
								$mappedDataArray[$field][$termAltKey] = htmlspecialchars($innerContent);
							}
						}
					} else {
						if ( t3lib_div::inList($secureFields,$field) ) {
							$mappedDataArray[$field] = htmlspecialchars($fieldContent);
						}
					}
				}
			}
			
			// TODO: hook "mappingPostProcessing"
			
			if (!empty($dataSourceConfigArray['mapping.']['uid.']['field'])) {
				$dataArray[$row[$dataSourceConfigArray['mapping.']['uid.']['field']]] = $mappedDataArray;
			} else {
				$dataArray[] = $mappedDataArray;
			}			
		}

		return $dataArray;
	}
	
	/**
	 * get the storage pids; cascade: type > dataSource > globalConfig
	 *
	 * @param string	$typeConfigArray 
	 * @return array	An array containing the storage PIDs of the type given by
	 * @author Jochen Rau
	 */
	function getStoragePidsArray($typeConfigArray) {
		$storagePidsArray = array();		
		$dataSource = $typeConfigArray['dataSource'] ? $typeConfigArray['dataSource'] : 'default';
		if ( $typeConfigArray['storagePids'] ) {
			$storagePidsArray = t3lib_div::trimExplode(',',$typeConfigArray['storagePids']);
		} elseif ( $this->conf['dataSources.'][$dataSource.'.']['storagePids'] ) {
			$storagePidsArray = t3lib_div::trimExplode(',',$this->conf['dataSources.'][$dataSource.'.']['storagePids']);
		} elseif ( $this->conf['storagePids']) {
			$storagePidsArray = t3lib_div::trimExplode(',',$this->conf['storagePids']);
		}
		
		return $storagePidsArray;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_mapper.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_mapper.php']);
}
?>