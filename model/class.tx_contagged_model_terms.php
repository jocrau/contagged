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
class tx_contagged_model_terms {
	var $conf; // the TypoScript configuration array
	var $cObj;
	var $controller;
	var $tablesArray; // array of all tables in the database
	
	function tx_contagged_model_terms($controller) {
		$this->controller = $controller;
		$this->conf = $controller->conf;
		$this->cObj = $controller->cObj;
		
		// build an array of tables in the database
		$tablesArray = array();
		$tablesResult = mysql_list_tables(TYPO3_db);
		if (!mysql_error()) {
			while($table = mysql_fetch_assoc($tablesResult)) {
				$this->tablesArray[] = current($table);
			}
		}
	}

	function getAllTerms() {
		$typesArray = $this->conf['types.'];
		$termsArray = array();
		foreach ($typesArray as $type => $typeConfigArray) {

			// get the storage pids
			if ( $typeConfigArray['storagePids'] ) {
				$storagePids = $typeConfigArray['storagePids'];
			} elseif ( $this->conf['dataSources.'][$typeConfigArray['dataSource'].'.']['storagePids'] ) {
				$storagePids = $this->conf['dataSources.'][$typeConfigArray['dataSource'].'.']['storagePids'];
			} elseif ( $this->conf['storagePids'] ) {
				$storagePids = $this->conf['storagePids'];
			} else {
				$storagePids = '';
			}

			$dataSource = $typeConfigArray['dataSource'] ? $typeConfigArray['dataSource'] : 'default';

			// get an array of all data rows in the configured tables
			if ( $storagePids!='' ) {
				$termsArray = t3lib_div::array_merge($termsArray,$this->getTermsArray($dataSource,$storagePids));
			}
		}
		
		uasort($termsArray,array($this,"sortByTermAscending"));
		
		return $termsArray;
	}
	
	function sortByTermAscending($termArrayA,$termArrayB) {
		// TODO: improve sorting (UTF8, configurable, localized->hook)
		// strcasecmp() internally converts the two strings it is comparing to lowercase, based on the server locale settings. As such, it
		// cannot be relied upon to be able to convert appropriate multibyte characters in UTF-8 to lowercase and, depending on the actual
		// locale, may have internally corrupted the UTF-8 strings it is comparing, having falsely matched byte sequences. It won’t actually
		// damage the UTF-8 string but the result of the comparison cannot be trusted. (Ref. http://www.phpwact.org/php/i18n/utf-8)
		// TODO remove; just a hack
		$termsArray = array($termArrayA['term'],$termArrayB['term']);
		// $GLOBALS['TSFE']->csConvObj->convArray($termsArray,'utf-8','iso-8859-1');
		$termsArrayBefore = $termsArray;
		sort($termsArray,SORT_LOCALE_STRING);
		$termsArrayAfterwards = $termsArray;
		// debug($termsArrayBefore,'before');debug($termsArrayAfterwards,'after');
		if ($termsArrayBefore[0]==$termsArrayAfterwards[0]) {
			$result = -1;
		} else {
			$result = 1;
		}

		return $result;
	}

	/**
		* Build an array of the entries in the tables
		*
		* @return	An array with the terms an their configuration
		*/
	function getTermsArray($dataSource,$storagePids='') {
		$dataArray = array();
		$terms = array();

		$dataSourceConfigArray = $this->conf['dataSources.'][$dataSource . '.'];
		$sourceName = $dataSourceConfigArray['sourceName'];
		
		// check if the table exists in the database
		if (t3lib_div::inArray($this->tablesArray,$sourceName) ) {
			// Build WHERE-clause
			$whereClause = '1=1';
			$whereClause .= $storagePids ? ' AND pid IN ('.$storagePids.')' : '';
			$whereClause .= $dataSourceConfigArray['hasSysLanguageUid'] ? ' AND (sys_language_uid='.intval($GLOBALS['TSFE']->sys_language_uid) . ' OR sys_language_uid=-1)' : '';
			$whereClause .= tslib_cObj::enableFields($sourceName);

			// execute SQL-query
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*', // SELECT ...
				$sourceName, // FROM ...
				$whereClause // WHERE ..
				);

			// map the fields
			$mapper = new tx_contagged_model_mapper($this->controller);
			$dataArray = $mapper->getDataArray($result,$dataSource);
		}

		// TODO piVars as a data source
		// 		$result = array(
		// 			array(
		// 				'uid' => 5,
		// 				'pid' => 94,
		// 				'term' => 'nullam',
		// 				'term_type' => 'definition',
		// 				'desc_short' => 'Test'			
		// 				)
		// 			);
		// krumo($result);

		return $dataArray;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_terms.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/model/class.tx_contagged_model_terms.php']);
}
?>