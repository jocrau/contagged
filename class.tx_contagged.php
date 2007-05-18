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

// TODO: change type of extension; use MVC-pattern
// FIXIT: problem with nesting def_block elements
// TODO: make text field of alternative terms (backend) multiline

require_once (PATH_tslib . 'class.tslib_pibase.php');
require_once (PATH_t3lib . 'class.t3lib_parsehtml.php');

/**
	* The main class to parse,tag and replace specific terms of the content.
	* 
	* $Id$
	* 
	* @author	Jochen Rau <j.rau@web.de>
	* @package	TYPO3
	* @subpackage	tx_contagged
	*/
/**
	* [CLASS/FUNCTION INDEX of SCRIPT]
	*/
class tx_contagged extends tslib_pibase {
	var $prefixId = 'tx_contagged'; // same as class name
	var $scriptRelPath = 'pi1/class.tx_contagged.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $pi_checkCHash = true;
	var $dataTable = 'tx_contagged_terms';
	var $conf; // the TypoScript configuration array
	var $termsFoundArray = array(); // an array of main terms for each term found in the cObj

	/**
		* The method for parsing, tagging and linking the terms in a cObj
		*
		* @param	string		$content: The content of the cObj
		* @param	array		$conf: The configuration
		* @return	The parsed and tagged content that is displayed on the website
		*/
	function main($content, $conf) {
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];
		
		// exit if the page should be skipped
		if ($this->isPageToSkip()) {
			return $content;
			exit;
		}

		// get an array of all data rows in the table "tx_contagged_terms"
		$this->termsArray = $this->getTermsArray();

		// build a list of tags used in the type definitions
		// these tags will be omitted while parsing the text
		foreach ($this->conf['types.'] as $key => $type) {
			if (isset($type['tag']) && !in_array($type['tag'],$tagArray)) {
				$tagArray[] = $type['tag'];
			}
			$tagList = implode(',',$tagArray);
		}
		if ($this->conf['tagsExclude']) {
			$tagList .= ',' . $this->conf['tagsExclude'];
		}

		// iterate through all terms
		foreach ($this->termsArray as $termArray) {

			unset($typeConfigArray);  // this should prevend a wrong configuration if no config is available 
			$typeConfigArray = $this->getTypeConfigArray($termArray);

			// build the tag enclosing the term
			if ( isset($typeConfigArray['tag']) ) {
				// get the attributes
				$langAttribute = $this->getLangAttribute($typeConfigArray,$termArray);
				$titleAttribute = $this->getTitleAttribute($typeConfigArray,$termArray);
				$cssClassAttribute = $this->getCssClassAttribute($typeConfigArray,$termArray);
				// concatenate the tag
				$before = '<' . $typeConfigArray['tag'] . $titleAttribute . $cssClassAttribute . $langAttribute . '>';
				$after = '</' . $typeConfigArray['tag'] . '>';
			}

			// Set the maximum amount of replaced terms to a standard
			// $maxOccur = $typeConfigArray['maxOccur'];
			$maxOccur ? $maxOccur : $maxOccur = 9999;
			// reset the occurancies of the actual term
			// $occuranciesOfTerm = 0;

			foreach ( $termArray['terms'] as $term ) {

				// $occuranciesOfTerm = $occuranciesOfTerm + $termsfound;
				// $termsFound = 0; // reset the amount of terms found in a cObj
				$parseObj = t3lib_div::makeInstance('t3lib_parsehtml');
				$content = $parseObj->splitIntoBlock($tagList,$content);
				foreach($content as $intKey => $HTMLvalue) {
					if (!($intKey%2)) {
						// the following code was inspired from the class.tslib_content.php, line 4265ff
						$newstring = '';
						do {
							// split the content in two pieces separated by the term, but don't do that if we are inside a tag to prevent nested tags
							// RegEx explained: (Match the term if it is NOT followed by a closing tag) OR, if no occurency was found
							// (Match the term that is followed by a closing tag with an opening tag inbetween). UFF!
							// TODO Unit testing for the Regex in preg_split; because this is the most critical operation
							// TODO modifier i: makes RegEx case insensitive; this should be configurable through TS
							$pieces = preg_split('#(?<=\W|\A)' . preg_quote($term) . '(?=\W|\Z)(?!.*</(' . $tagList . ')>)|(?<=\W|\A)' . preg_quote($term) . '(?=\W|\Z)(?=.*<(' . $tagList . ')(?=.*</\2))#Ui', $content[$intKey], 2);
							$newstring .= $pieces[0];

							// flag: $inTag=true if we are inside a tag < here we are >
							if ( strrpos($pieces[0],'<') > strrpos($pieces[0],'>') ) {
								$inTag = true;
							} else {
								$inTag = false;
							}

							// the  term is handled as $matchedTerm, so it doesn't conflict with case (in)sensitivity of the RegEx
							$matchLength = strlen($content[$intKey]) - (strlen($pieces[0]) + strlen($pieces[1]));
							$matchedTerm = substr($content[$intKey], strlen($pieces[0]), $matchLength);
							$GLOBALS['TSFE']->register['contagged_matchedTerm'] = $matchedTerm;
							if ( trim($matchedTerm) && ($inTag === false) && ($occuranciesOfTerm < $maxOccur) ) {
								// Build an array of terms found in the content.
								// This will be used to store them as keywords of the page.
								// The term used will be the replaced term. If there is no replacement the main term will be used.
								$this->termsFoundArray[] = $termArray['term_replace']?$termArray['term_replace']:$termArray['term_main'];
								
								$this->replaceMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray);
								$this->linkMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray);

								// call stdWrap to handle the matched term via TS
								// TODO: wrapping inside AND outside the a-tag should be enabled
								if ($typeConfigArray['stdWrap.']) {
									$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['stdWrap.']);
								}

								$matchedTerm = $before . $matchedTerm . $after;

								// $termsFound++;

							}
							// concatenate the term again
							$newstring .= $matchedTerm;
							$content[$intKey] = $pieces[1];
						} while ($pieces[1]);
						$content[$intKey] = $newstring;
					}
				}
				
			$content = implode('',$content);	
			
			}
		}
		
		if ($this->conf['updateKeywords'] > 0) {
			$this->insertKeywords();
		}

		return $content;

	}

	function insertKeywords() {
		// make a list of unique terms found in the content
		$this->termsFoundArray = array_unique($this->termsFoundArray);
		$termsFoundList = implode(',',$this->termsFoundArray);
		// build an array passed to the UPDATE query
		$updateArray = array($this->prefixId . '_keywords' => $termsFoundList);
//		$updateArray = array('keywords' => $termsFoundList);
//		debug($updateArray);
		// execute sql-query
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', // TABLE ...
			'uid=' . $GLOBALS['TSFE']->id, // WHERE ...
			$updateArray
		);
	}

	function getTypeConfigArray($termArray) {
		// shortcut to the TS configuration array of the current type of term
		$typeConfigArray = $this->conf['types.'][$termArray['term_type'] . '.'];

		// load several fields into the register to be handled by the TS setup
		$GLOBALS['TSFE']->register['contagged_uid'] = $termArray['uid'];
		$GLOBALS['TSFE']->register['contagged_desc_short'] = $termArray['desc_short'];
		$GLOBALS['TSFE']->register['contagged_desc_long'] = $termArray['desc_long'];
		$GLOBALS['TSFE']->register['contagged_term_main'] = $termArray['term_main'];
		$GLOBALS['TSFE']->register['contagged_term_replace'] = $termArray['term_replace'];
		$GLOBALS['TSFE']->register['contagged_link'] = $termArray['link'];

		return $typeConfigArray;

	}

	function linkMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray) {
		// check conditions if the term should be linked to a list page
		$makeLink = $this->checkLocalGlobal($typeConfigArray,'linkToListPage');
		if ( ($termArray['desc_long'] == '') || ($termArray['exclude'] > 0) ) {
			$makeLink = false;
		}

		// link the matched term to the front-end list page
		if ($makeLink) {
			unset($typolinkConf); // TODO Is it necessary to unset the $typoLinkConf?
			$listPage = ($typeConfigArray['listPage']?$typeConfigArray['listPage']:$this->conf['listPage']);
			$GLOBALS['TSFE']->register['contagged_list_page'] = $termArray['uid'];
			$typolinkConf['parameter'] = (int) $listPage;
			$typolinkConf['useCacheHash'] = 1;
			$typolinkConf['additionalParams'] =
				'&' . $this->prefixId . '[back]=' . $GLOBALS['TSFE']->id .
				'&' . $this->prefixId . '[uid]=' . $termArray['uid'] .
				'&' . $this->prefixId . '[type]=' . $termArray['term_type'];
			$matchedTerm = $this->cObj->typolink($matchedTerm, $typolinkConf);
		}
	}

	function replaceMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray) {
		// replace the term
		// TODO improve upper/lower case handling (UTF8)
		if ( $termArray['term_replace'] && $typeConfigArray['replaceTerm'] == 1 ) {
			// if the first letter of the matched term is upper case
			// make the first letter of the replacing term also upper case
			if ( preg_match('#[A-ZÄÖÜ]#', substr($matchedTerm,0,1) ) != 0 ) {
				$matchedTerm = ucfirst($termArray['term_replace']);
			} else {
				$matchedTerm = $termArray['term_replace'];
			}
		}
	}

	function checkLocalGlobal($typeConfigArray,$attributeName) {
		if ( isset($typeConfigArray[$attributeName]) ) {
			$addAttribute = ($typeConfigArray[$attributeName] > 0) ? true : false;
		} else {
			$addAttribute = ($this->conf[$attributeName] > 0) ? true : false;
		}
		
		return $addAttribute;
	}

	/**
		* If the language of the term is undefined, 
		* or the page language is the same as language of the term,
		* then the lang attribute will not be shown. 
		* 
		* If the terms language is defined and different from the page language, 
		* then the language attribute is added.
		*/
	function getLangAttribute($typeConfigArray,$termArray) {
		// get page language
		if ($GLOBALS['TSFE']->config['config']['language']) {
			$pageLanguage = $GLOBALS['TSFE']->config['config']['language'];
		} else {
			$pageLanguage = substr($GLOBALS['TSFE']->config['config']['htmlTag_langKey'],0,2);
		}
		// build language attribute if the page language is different from the terms language
		if ( $this->checkLocalGlobal($typeConfigArray,'addLangAttribute') && !empty($termArray['term_lang']) && ( $pageLanguage!=$termArray['term_lang'] ) ) {
			$langAttribute = ' lang="' . $termArray['term_lang'] . '"';
			$langAttribute .= ' xml:lang="' . $termArray['term_lang'] . '"';
		}

		return $langAttribute;
	}

	function getTitleAttribute($typeConfigArray,$termArray) {
		if ($this->checkLocalGlobal($typeConfigArray,'addTitleAttribute') && isset($termArray['desc_short'])) {
			$titleAttribute = ' title="' . $termArray['desc_short'] . '"';
		}
		
		return $titleAttribute;
	}

	function getCssClassAttribute($typeConfigArray,$termArray) {
		if ($this->checkLocalGlobal($typeConfigArray,'addCssClassAttribute')) {
			if ( $typeConfigArray['cssClass'] ) {
				$cssClassAttribute = $this->pi_classParam($typeConfigArray['cssClass']);
			} else {
				$cssClassAttribute = $this->pi_classParam($termArray['term_type']);
			}
		}

		return $cssClassAttribute;
	}

	/**
		* Test, if the current page should be skipped
		*
		* @return	True, if the page should be skipped
		*/
	function isPageToSkip() {
		$result = true; // true, if the page should be skipped
		$currentPageUid = $GLOBALS['TSFE']->id;
		// get rootline of the current page
		$rootline = $GLOBALS['TSFE']->sys_page->getRootline($currentPageUid);
		// build an array of uids of pages the rootline
		for ($i = count($rootline) - 1; $i >= 0; $i--) {
			$pageUidsInRootline[] = $rootline["$i"]['uid'];
		}
		// check if the root page is in the rootline of the current page
		$includeRootPagesUids = t3lib_div :: trimExplode(',', $this->conf['includeRootPages'], 1);
		foreach ($includeRootPagesUids as $includeRootPageUid) {
			if (t3lib_div :: inArray($pageUidsInRootline, $includeRootPageUid))
				$result = false;
		}
		$excludeRootPagesUids = t3lib_div :: trimExplode(',', $this->conf['excludeRootPages'], 1);
		foreach ($excludeRootPagesUids as $excludeRootPageUid) {
			if (t3lib_div :: inArray($pageUidsInRootline, $excludeRootPageUid))
				$result = true;
		}
		if (t3lib_div::inList($this->conf['includePages'], $currentPageUid)) {
			$result = false;
		}
		if (t3lib_div::inList($this->conf['excludePages'], $currentPageUid)) {
			$result = true;			
		}

		return $result;
	}

	/**
		* Build an array of the entries in the table "tx_contagged_terms"
		*
		* @return	An array with the data of the table "tx_contagged_terms"
		*/
	function getTermsArray() {
		// execute sql-query
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', // SELECT ...
		$this->dataTable, // FROM ...
		'1=1' . tslib_cObj :: enableFields($this->dataTable) // WHERE...
		);


		// build an array of entries in the table "tx_contagged_terms"
		$dataArray = array ();
		$terms = array ();
		foreach ($result as $row) {
			// build an array of alternative shortcurts (term_alt) and add the main term (term_main)
			$terms = t3lib_div :: trimExplode(chr(10), htmlspecialchars($row['term_alt']), $onlyNonEmptyValues = 1);
			$terms[] = trim(htmlspecialchars($row['term_main']));
			// TODO sort the array by descending length of value string; in combination with the htmlparser this will prevend nesting
			$desc_long = $this->cObj->parseFunc($row['desc_long'],$conf='',$ref='< lib.parseFunc_RTE');
			// $desc_long = preg_replace('/(\015\012)|(\015)|(\012)/','<br />',$row['desc_long']);
			// $desc_long = trim(htmlspecialchars($desc_long));
			// put it all together
			$dataArray[] = array (
				'uid' => $row['uid'],
				'term_main' => trim(htmlspecialchars($row['term_main'])),
				'terms' => $terms,
				'term_type' => $row['term_type'],
				'term_lang' => $row['term_lang'],
				'term_replace' => trim(htmlspecialchars($row['term_replace'])),
				'desc_short' => trim(htmlspecialchars($row['desc_short'])),
				'desc_long' => $desc_long,
				'link' => $row['link'],
				'exclude' => $row['exclude']
				);
		}

		return $dataArray;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']);
}
?>