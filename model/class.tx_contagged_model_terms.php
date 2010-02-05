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
require_once (t3lib_extMgm::extPath('contagged') . 'model/class.tx_contagged_model_mapper.php');

/**
 * The model of contagged.
 *
 * @author	Jochen Rau <j.rau@web.de>
 * @package	TYPO3
 * @subpackage	tx_contagged_model_terms
 */
class tx_contagged_model_terms implements t3lib_Singleton {
	var $conf; // the TypoScript configuration array
	var $controller;
	var $tablesArray = array(); // array of all tables in the database
	var $dataSourceArray = array();
	var $terms = array();
	var $configuredSources = array();
	var $listPagesCache = array();

	function __construct($controller) {
		$this->controller = $controller;
		$this->conf = $controller->conf;
		if (!is_object($this->cObj)) {
			$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		}

		$this->mapper  = t3lib_div::makeInstance('tx_contagged_model_mapper', $this->controller);

		// build an array of tables in the database
		$this->tablesArray = $GLOBALS['TYPO3_DB']->admin_get_tables(TYPO3_db);
		
		if (is_array($this->conf['dataSources.'])) {
			foreach ($this->conf['dataSources.'] as $dataSource => $sourceConfiguration) {
				$this->configuredSources[$sourceConfiguration['sourceName']] = substr($dataSource, 0, -1);
			}
		} else {
			throw new RuntimeException('No configuration. Please include the static template.');
		}
		
		$typesArray = $this->conf['types.'];
		foreach ($typesArray as $type=>$typeConfigArray) {
			$storagePidsArray = $this->getStoragePidsArray($typeConfigArray);
			$dataSource = $typeConfigArray['dataSource'] ? $typeConfigArray['dataSource'] : 'default';
			foreach ($storagePidsArray as $pid) {
				// if there is an entry for the data source: check for duplicates before adding the pid
				// otherwise: create a new entry and add the pid
				if (is_array($this->dataSourceArray[$dataSource]) && !in_array(intval($pid),$this->dataSourceArray[$dataSource])) {
					$this->dataSourceArray[$dataSource][] = intval($pid);
				} else {
					$this->dataSourceArray[$dataSource][] = intval($pid);
				}
			}
		}
		
	}

	function findAllTerms($additionalWhereClause = '') {
		if (empty($this->terms)) {
			foreach ($this->dataSourceArray as $dataSource => $storagePidsArray ) {
				$this->terms = array_merge($this->terms, $this->fetchTermsFromSource($dataSource,$storagePidsArray));
			}
		}
		return $this->terms;
	}
	
	function findAllTermsToListOnPage($pid = NULL) {
		$terms = $this->findAllTerms(' AND exclude=0');
		if ($pid === NULL) $pid = $GLOBALS['TSFE']->id;
		$filteredTerms = array();
		foreach ($terms as $key => $term) {
			$typeConfigurationArray = $this->conf['types.'][$term['term_type'] . '.'];
			$listPidsArray = $this->getListPidsArray($term['term_type']);
			if (($typeConfigurationArray['dontListTerms'] == 0) && (in_array($pid, $listPidsArray) || is_array($GLOBALS['T3_VAR']['ext']['contagged']['index'][$pid][$key])) ) {
				$filteredTerms[$key] = $term;
			}
		}
		uasort($filteredTerms, array($this, 'sortByTermAscending'));
		return $filteredTerms;
	}

	function sortByTermAscending($termArrayA, $termArrayB) {
		return strnatcasecmp($termArrayA['term'], $termArrayB['term']);
	}
	
	function findTermByUid($dataSource, $uid) {
		$additionalWhereClause = ' AND uid=' . intval($uid);
		$terms = $this->fetchTermsFromSource($dataSource, $storagePidsArray, $additionalWhereClause);
		$this->fetchRelatedTerms($terms);
		if (is_array($terms) && count($terms) > 0) {
			return array_shift($terms);
		} else {
			return NULL;
		}
	}

	/**
	 * Build an array of the entries in the tables
	 *
	 * @param	[type]		$dataSource: ...
	 * @param	[type]		$storagePids: ...
	 * @return	An		array with the terms an their configuration
	 */
	function fetchTermsFromSource($dataSource, $storagePidsArray= array(), $additionalWhereClause = '') {
		$dataArray = array();
		$dataSourceConfigArray = $this->conf['dataSources.'][$dataSource . '.'];
		$tableName = $dataSourceConfigArray['sourceName'];
		// check if the table exists in the database
		if (array_key_exists($tableName, $this->tablesArray) ) {				
			// Build WHERE-clause
			$whereClause = '1=1';
			$whereClause .= count($storagePidsArray) > 0 ? ' AND pid IN (' . implode(',',$storagePidsArray) . ')' : '';
			$whereClause .= $dataSourceConfigArray['hasSysLanguageUid'] ? ' AND (sys_language_uid=' . intval($GLOBALS['TSFE']->sys_language_uid) . ' OR sys_language_uid=-1)' : '';
			$whereClause .= $this->cObj->enableFields($tableName);
			$whereClause .= $additionalWhereClause;

			// execute SQL-query
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*', // SELECT ...
				$tableName, // FROM ...
				$whereClause // WHERE ..
				);
			// map the fields
			$mappedResult = $this->mapper->getDataArray($result,$dataSource);
		}
		if (is_array($mappedResult)) {
			foreach ($mappedResult as $result) {
				$dataArray[$result['sourceName'] . '_' . $result['uid']] = $result;
			}
		}
		// TODO piVars as a data source
		return $dataArray;
	}
	
	function fetchRelatedTerms(&$dataArray) {
		$newDataArray = array();
		foreach ($dataArray as $key => $termArray) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid_foreign, tablenames', // SELECT ...
				'tx_contagged_related_mm', // FROM ...
				'uid_local=' . $termArray['uid'], // WHERE ..
				'sorting'
				);

			if (!empty($result)) {
				$termArray['related'] = array();
				foreach ($result as $row) {
					$dataSource = $this->configuredSources[$row['tablenames']];
					if ($dataSource !== NULL) {
						$termArray['related'][] = array('source' => $dataSource, 'uid' => $row['uid_foreign']);
					}
				}
			} else {
				$termArray['related'] = NULL;
			}
			$newDataArray[] = $termArray;
		}
		$dataArray = $newDataArray;
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
		if (!empty($typeConfigArray['storagePids'])) {
			$storagePidsArray = t3lib_div::intExplode(',',$typeConfigArray['storagePids']);
		} elseif (!empty($this->conf['dataSources.'][$dataSource.'.']['storagePids']) ) {
			$storagePidsArray = t3lib_div::intExplode(',',$this->conf['dataSources.'][$dataSource.'.']['storagePids']);
		} elseif (!empty($this->conf['storagePids'])) {
			$storagePidsArray = t3lib_div::intExplode(',',$this->conf['storagePids']);
		}
		return $storagePidsArray;
	}

	/**
	 * get the lsit pids; cascade: type > globalConfig
	 *
	 * @param string	$typeConfigArray 
	 * @return array	An array containing the list PIDs of the type given by
	 * @author Jochen Rau
	 */
	function getListPidsArray($termType) {
		if (!isset($this->listPagesCache[$termType])) {		
			$listPidsArray = array();
			if (!empty($this->conf['types.'][$termArray['term_type'].'.']['listPages'])) {
				$this->listPagesCache[$termType] = t3lib_div::intExplode(',',$this->conf['types.'][$termArray['term_type'].'.']['listPages']);
			} elseif (!empty($this->conf['listPages'])) {
				$this->listPagesCache[$termType] = t3lib_div::intExplode(',',$this->conf['listPages']);
			}
		}
		return $this->listPagesCache[$termType];
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_terms.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_terms.php']);
}
?>