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

require_once (PATH_tslib . 'class.tslib_pibase.php');
require_once (PATH_t3lib . 'class.t3lib_parsehtml.php');
require_once (t3lib_extMgm::extPath('contagged') . 'model/class.tx_contagged_model_terms.php');

/**
	* The main class to parse,tag and replace specific terms of the content.
	* 
	* @author	Jochen Rau <j.rau@web.de>
	* @package	TYPO3
	* @subpackage	tx_contagged
	*/
class tx_contagged extends tslib_pibase {
	var $prefixId = 'tx_contagged';
	var $scriptRelPath = 'class.tx_contagged.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $pi_checkCHash = true;
	var $conf; // the TypoScript configuration array
	var $termsFoundArray = array(); // an array of main terms for each term found in the cObj
	var $specialExcludeTag = 'exparse'; // content tagged by <exparse>|</exparse> will not be parsed

	/**
		* The method for parsing, tagging and linking the terms in a cObj
		*
		* @param	string		$content: The content of the cObj
		* @return	The parsed and tagged content that is displayed on the website
		*/
	function main($content,$conf) {
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];

		// exit if the content should be skipped
		if ($this->isContentToSkip()) {
			return $content;
			exit;
		}

		// get an array of all type configurations
		$typesArray = $this->conf['types.'];
		
		// get the model (an associated array of terms)
		$model = new tx_contagged_model_terms($this);
		$termsArray = $model->getAllTerms();
		
		// get a comma separated list of all tags which should be omitted
		$tagsToOmitt = $this->getTagsToOmitt();
		// debug($termsArray); return $content; exit;

		// iterate through all terms
		foreach ($termsArray as $termArray) {

			unset($typeConfigArray);
			$typeConfigArray = $typesArray[$termArray['term_type'] . '.'];

			$this->registerFields($typeConfigArray,$termArray);

			// build the tag enclosing the term
			if ( !empty($typeConfigArray['tag']) ) {
				// get the attributes
				$langAttribute = $this->getLangAttribute($typeConfigArray,$termArray);
				$titleAttribute = $this->getTitleAttribute($typeConfigArray,$termArray);
				$cssClassAttribute = $this->getCssClassAttribute($typeConfigArray,$termArray);
				// concatenate the tag
				$before = '<' . $typeConfigArray['tag'] . $titleAttribute . $cssClassAttribute . $langAttribute . '>';
				$after = '</' . $typeConfigArray['tag'] . '>';
			}

			// get the maximum amount of replaced terms
			$maxOccur = $typeConfigArray['maxOccur'] ? (int)$typeConfigArray['maxOccur'] : 9999;
			
			$terms = array();
			$terms = $termArray['term_alt'];
			$terms[] = $termArray['term_main'];
			foreach ( $terms as $term ) {
				$termsFound = 0; // reset the amount of terms found in a cObj
				
				// build main RegEx
				// stdWrap for the term to search for; usefull to realize custom tags like <person>|</person>
				$term = $this->cObj->stdWrap($term,$typeConfigArray['termStdWrap.']);
				if ( $this->checkLocalGlobal($typeConfigArray,'termIsRegEx')>0 ) {
					$regEx = $termArray['term_main'];
				} else {
					$regEx = '/(?<=\P{L}|^)' . preg_quote($term,'/') . '(?=\P{L}|$)/';
				}

				// TODO split recursively
				$parseObj = t3lib_div::makeInstance('t3lib_parsehtml');
				$content = $parseObj->splitIntoBlock($tagsToOmitt,$content);
				// debug($content);
				foreach($content as $intKey => $HTMLvalue) {
					if (!($intKey%2)) {
						// split the content in two pieces separated by the matched term
						// this was inspired by class.tslib_content.php, line 4265ff
						$newstring = '';
						do {
							// split the content in two pieces separated by the term
							$pieces = preg_split( $regEx . $this->conf['modifier'], $content[$intKey], 2 );

							// Flag: $inTag=true if we are inside a tag < here we are > or if we are inside an entity (eg. &nbsp;)
							// first RegEx see "Friedl, Jeffrey: Reguläre Audrücke. p.204"
							if ( preg_match('/<("[^"]*"|\'[^\']*\'|[^\'">])*$/u',$pieces[0])>0 && preg_match('/^("[^"]*"|\'[^\']*\'|[^\'"<])*>/u',$pieces[1])>0 ) {
								$inTag = true;
							} elseif (preg_match('/&.{0,5}$/u',$pieces[0])>0 && preg_match('/^.{0,5};/u',$pieces[1])>0) {
								$inTag = true;
							} else {
								$inTag = false;
							}

							// support for joined words (with a dash)
							$preMatch = '';
							$postMatch = '';
							if ($this->checkLocalGlobal($typeConfigArray,'checkPreAndPostMatches')>0) {
								preg_match('/(?<=\P{L})[\p{L}\p{Pd}]*\p{Pd}$/Uuis', $pieces[0], $preMatch);
								preg_match('/^\p{Pd}[\p{L}\p{Pd}]*(?=\P{L})/Uuis', $pieces[1], $postMatch);								
							}

							// add the first string ($pieces[0]) to the new string without the pre matched part
							$newstring .= $GLOBALS['TSFE']->csConvObj->substr('utf-8',$pieces[0],0,$GLOBALS['TSFE']->csConvObj->strlen('utf-8',$pieces[0])-$GLOBALS['TSFE']->csConvObj->strlen('utf-8',$preMatch[0]));

							// the term is handled as $matchedTerm, so it doesn't conflict with case (in)sensitivity of the RegEx
							$matchLength = $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$content[$intKey]) - ($GLOBALS['TSFE']->csConvObj->strlen('utf-8',$pieces[0]) + $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$pieces[1])) + $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$preMatch[0]) + $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$postMatch[0]);
							$matchedTerm = $GLOBALS['TSFE']->csConvObj->substr('utf-8',$content[$intKey], $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$pieces[0])-$GLOBALS['TSFE']->csConvObj->strlen('utf-8',$preMatch[0]), $matchLength);
							// do something with the matched term (replace, stdWrap, link, tag)
							if ( trim($matchedTerm) && $inTag==false && ($termsFound<$maxOccur) ) {
								$this->replaceMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray);
								$GLOBALS['TSFE']->register['contagged_matchedTerm'] = $matchedTerm;

								// call stdWrap to handle the matched term via TS BEFORE it is wraped with a-tags
								if ($typeConfigArray['preStdWrap.']) {
									$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['preStdWrap.']);
								}

								$this->linkMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray);

								// call stdWrap to handle the matched term via TS AFTER it was wrapped with a-tags
								if ($typeConfigArray['postStdWrap.'] or $typeConfigArray['stdWrap.']) {
									$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['postStdWrap.']);
									$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['stdWrap.']); // for compatibility with < v0.0.5
								}

								if ( !empty($typeConfigArray['tag']) ) {
									$matchedTerm = $before . $matchedTerm . $after;
								}

								// TODO updated keywords are only taken from the last cObj that has been parsed
								// Build an array of terms found in the contentObject.
								// This will be used to store them as keywords of the page.
								$this->termsFoundArray[] = $termArray['term'];
								$termsFound++;
							}
							// concatenate the term again
							$newstring .= $matchedTerm;
							$content[$intKey] = $GLOBALS['TSFE']->csConvObj->substr('utf-8',$pieces[1],$GLOBALS['TSFE']->csConvObj->strlen('utf-8',$postMatch[0]));
						} while ($pieces[1]);
						$content[$intKey] = $newstring;
					}
				}
				$content = implode('',$content);
			}
		}

		// update the keywords (field "tx_contagged_keywords" in table "page")
		if ($this->conf['updateKeywords'] > 0) {
			$this->insertKeywords();
		}

		return $content;

	}

	function getTagsToOmitt() {
		// build a list of tags used in the type definitions
		// these tags will be omitted while parsing the text
		$tagArray = array();
		// if configured: build a list of tags used by the term definitions
		if ($this->conf['autoExcludeTags']>0) {;
			foreach ($this->conf['types.'] as $key => $type) {
				if (!empty($type['tag']) && !in_array($type['tag'],$tagArray)) {
					$tagArray[] = $type['tag'];
				}
			}
		}
		// if there are tags to exclude: add them to the list
		if ($this->conf['excludeTags']) {
			$tagArray[] = $this->conf['excludeTags'];
		}
		// add the special exclude tag <exparse> to the list
		$tagArray[] = $this->specialExcludeTag;
		$tagList = implode(',',$tagArray);
		
		return $tagList;
	}

	function insertKeywords() {
		// make a list of unique terms found in the content
		$this->termsFoundArray = array_unique($this->termsFoundArray);
		$termsFoundList = implode(',',$this->termsFoundArray);
		$GLOBALS['TSFE']->register['contagged_keywords'];
		// build an array to be passed to the UPDATE query
		$updateArray = array($this->prefixId . '_keywords' => $termsFoundList);
		// execute sql-query
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'pages', // TABLE ...
			'uid=' . $GLOBALS['TSFE']->id, // WHERE ...
			$updateArray
			);
	}

	function registerFields($typeConfigArray,$termArray) {
		// Replace <p></p> with <br/>; Idea from Markus Timtner. Thank you!
		// TODO: strip or replace all block-tags
		if ($typeConfigArray['stripBlockTags']>0) {
			$termArray['desc_long'] = preg_replace('/<p[^<>]*>(.*?)<\/p\s*>/ui','$1<br />',$termArray['desc_long']);			
		}

		// register all fields to be handled by the TS Setup
		foreach ($termArray as $key => $value) {
			$GLOBALS['TSFE']->register['contagged_'.$key] = $termArray[$key];			
		}
	}

	function linkMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray) {
		// check conditions if the term should be linked to a list page
		$makeLink = $this->checkLocalGlobal($typeConfigArray,'linkToListPage');
		if ( ($termArray['desc_long'] == '') || ($termArray['exclude']>0) ) {
			$makeLink = false;
		}
		if ($termArray['link']) {
			$makeLink = true;
		}

		// link the matched term to the front-end list page
		if ($makeLink) {
			unset($typolinkConf);
			if ($termArray['link']) {
				$parameter = $termArray['link'];
			} else {
				$parameter = $typeConfigArray['listPage']?$typeConfigArray['listPage']:$this->conf['listPage'];
			}
			
			$GLOBALS['TSFE']->register['contagged_list_page'] = $parameter;
			$typolinkConf['parameter'] = $parameter;
			// $typolinkConf['useCacheHash'] = 1; // TODO cHash
			// $typolinkConf['ATagParams'] = "rel='moodalbox'";
			$typolinkConf['additionalParams'] =
				'&' . $this->prefixId . '_pi1' . '[backPid]=' . $GLOBALS['TSFE']->id .
				'&' . $this->prefixId . '_pi1' . '[uid]=' . $termArray['uid'];
				//. '&' . $this->prefixId . '_pi1' . '[term_type]=' . $termArray['term_type'];
			$matchedTerm = $this->cObj->typolink($matchedTerm, $typolinkConf);
		}
	}

	function replaceMatchedTerm(&$matchedTerm,$typeConfigArray,$termArray) {
		$replaceTerm = $this->checkLocalGlobal($typeConfigArray,'replaceTerm');
		if ( $replaceTerm && $termArray['term_replace'] ) {
			// if the first letter of the matched term is upper case
			// make the first letter of the replacing term also upper case
			// (\p{Lu} stands for "unicode letter uppercase")
			if ( preg_match('/^\p{Lu}?/u',$matchedTerm)>0 ) {
				$matchedTerm = ucfirst($termArray['term_replace']); // TODO ucfirst is not UTF8 safe; it depends on the locale settings (they could be ASCII) 
			} else {
				$matchedTerm = $termArray['term_replace'];
			}
		}
	}

	function checkLocalGlobal($typeConfigArray,$attributeName) {
		if ( isset($typeConfigArray[$attributeName]) ) {
			$addAttribute = ($typeConfigArray[$attributeName]>0) ? true : false;
		} else {
			$addAttribute = ($this->conf[$attributeName]>0) ? true : false;
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
		if ($this->checkLocalGlobal($typeConfigArray,'addTitleAttribute') && !empty($termArray['desc_short'])) {
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
	function isContentToSkip() {
		$result = true; // true, if the page should be skipped
		$currentPageUid = $GLOBALS['TSFE']->id;

		// get rootline of the current page
		$rootline = $GLOBALS['TSFE']->sys_page->getRootline($currentPageUid);
		// build an array of uids of pages the rootline
		for ($i = count($rootline) - 1; $i >= 0; $i--) {
			$pageUidsInRootline[] = $rootline["$i"]['uid'];
		}
		// check if the root page is in the rootline of the current page
		$includeRootPagesUids = t3lib_div::trimExplode(',', $this->conf['includeRootPages'], 1);
		foreach ($includeRootPagesUids as $includeRootPageUid) {
			if (t3lib_div :: inArray($pageUidsInRootline, $includeRootPageUid))
				$result = false;
		}
		$excludeRootPagesUids = t3lib_div::trimExplode(',', $this->conf['excludeRootPages'], 1);
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
		if ( $GLOBALS['TSFE']->page['tx_contagged_dont_parse'] == 1) {
			$result = true;
		}
		if ( $this->cObj->getFieldVal('tx_contagged_dont_parse') == 1) {
			$result = true;
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']);
}
?>