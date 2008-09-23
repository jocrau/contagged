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
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
	*  GNU General Public License for more details.
	*
	*  This copyright notice MUST APPEAR in all copies of the script!
	***************************************************************/

require_once (PATH_tslib.'class.tslib_pibase.php');
require_once (PATH_t3lib.'class.t3lib_parsehtml.php');
require_once (t3lib_extMgm::extPath('contagged').'model/class.tx_contagged_model_terms.php');

/**
 * The main class to parse,tag and replace specific terms of the content.
 *
 * @author	Jochen Rau <j.rau@web.de>
 * @package TYPO3
 * @subpackage	tx_contagged
 */
class tx_contagged extends tslib_pibase {
	var $prefixId = 'tx_contagged';
	var $scriptRelPath = 'class.tx_contagged.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $conf; // the TypoScript configuration array

	/**
	 * The main method to parse, tag and link terms
	 *
	 * @param	string		$content: The content
	 * @param	array		$conf: The configuration array
	 * @return	string		The parsed and tagged content that is displayed on the website
	 */
	function main($content,$conf) {
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];
		
		// exit if the content should be skipped
		if ($this->isContentToSkip()) return $content;

		// $GLOBALS['TSFE']->additionalHeaderData['tx_contagged'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('contagged').'js/selecttext.js"></script>';
		// $GLOBALS['TSFE']->JSeventFuncCalls['onload']['tx_contagged'] = 'init_getSelectedText();';
		// $GLOBALS['TSFE']->JSeventFuncCalls['onmouseup']['tx_contagged'] = 'getSelectedText();';
		// $GLOBALS['TSFE']->divSection = '<span id="tx_contagged_new" style="visibility:hidden;position:relative;top:280px;left:10px;z-index:1000;"></span>';

		// TODO "New" icon
		// $storagePids = t3lib_div::trimExplode(',',$this->conf['storagePids'],1);
		// $mainStoragePid = $storagePids[0];
		// $panelConf = array(
		// 		'newRecordFromTable' => 'tx_contagged_terms',
		// 		'newRecordInPid' => $mainStoragePid,
		// 		);
		// $innerHTML = $this->cObj->editPanel('',$panelConf,'');
		// $GLOBALS['TSFE']->divSection = '<div id="tx_contagged_panel" class="" style="visibility:hidden;position:absolute;width:0px;top:0;left:0;z-index:1000;">'.$innerHTML.'</div>';
		

		// get an array of all type configurations
		$this->typesArray = $this->conf['types.'];

		// get the model (an associated array of terms)
		$modelClassName = t3lib_div::makeInstanceClassName('tx_contagged_model_terms');
		$model = new $modelClassName($this);
		$this->termsArray = $model->getTermsArray();

		// get a comma separated list of all tags which should be omitted
		$tagsToOmitt = $this->getTagsToOmitt();

		// TODO split recursively
		$parseObj = t3lib_div::makeInstance('t3lib_parsehtml');
		$splittedContent = $parseObj->splitIntoBlock($tagsToOmitt,$content);
		foreach((array)$splittedContent as $intKey => $HTMLvalue) {
			if (!($intKey%2)) {
				$positionsArray = array();
				// iterate through all terms
				foreach ($this->termsArray as $termKey=>$termArray) {
					// get the maximum amount of replaced terms
					$maxOccur = $this->typesArray[$termArray['term_type'] . '.']['maxOccur'] ? (int)$typeConfigArray['maxOccur'] : 9999;
					
					$typeConfigArray = $this->typesArray[$termArray['term_type'] . '.'];
					
					$terms = array();
					$terms[] = $termArray['term_main'];
					if ( $termArray['term_alt'] ) {
						$terms = array_merge($terms,$termArray['term_alt']);
					}
					// sort the array descending by length of the value, so the longest term will match
					usort($terms,array($this,'sortArrayByLengthDescending'));
					foreach ( $terms as $term ) {
						$this->getPositions($splittedContent[$intKey],&$positionsArray,$typeConfigArray,$term,$termArray,$termKey,$regEx,$tagsToOmitt,$maxOccur);
					}
				}
				ksort($positionsArray);
				$splittedContent[$intKey] = $this->doReplace($splittedContent[$intKey],$positionsArray);			
			}
		}
		$parsedContent = implode('',$splittedContent);
		

		// update the keywords (field "tx_contagged_keywords" in table "page")
		if ($this->conf['updateKeywords']>0) {
			$this->insertKeywords();
		}

		return $parsedContent;

	}
	
	function sortArrayByLengthDescending($a,$b) {
		if (strlen($a)==strlen($b)) {
			return 0;
		}
		
		return strlen($a)<strlen($b) ? 1 : -1;
	}

	function getRegEx($term,$termKey,$typeConfigArray) {
		$termArray = $this->termsArray[$termKey];
		// stdWrap for the term to search for; usefull to realize custom tags like <person>|</person>
		$regExTerm = $this->cObj->stdWrap($term,$typeConfigArray['termStdWrap.']);
		$regEx = '';
		if ( $this->checkLocalGlobal($typeConfigArray,'termIsRegEx')>0 ) {
			$regEx = $termArray['term_main'].$this->conf['modifier'];
		} else {
			$regEx = '/(?<=\W|^)' . preg_quote($regExTerm,'/') . '(?=\W|$)/' . $this->conf['modifier'];
		}
		
		return $regEx;
	}

	function getPositions($content,&$positionsArray,$typeConfigArray,$term,$termArray,$termKey,$regEx,$tagsToOmitt,$maxOccur) {
		$regEx = $this->getRegEx($term,$termKey,$typeConfigArray);
		preg_match_all($regEx,$content,$matchesArray,PREG_OFFSET_CAPTURE);
		$matchesArray = $matchesArray[0]; // only take the full pattern matches of the regEx
		
		// determine the maximum of recurrencies of the same term to be tagged
		$maxRecurrencies = $this->conf['maxRecurrencies'] ? min($this->conf['maxRecurrencies'], count($matchesArray)) : count($matchesArray);
		for ($i=0; $i < $maxRecurrencies; $i++) {
			$preContent = substr($content,0,$matchesArray[$i][1]);
			$postContent = substr($content,strlen($matchesArray[$i][0])+$matchesArray[$i][1]);

			// Flag: $inTag=true if we are inside a tag < here we are >
			$inTag = FALSE;
			if ( preg_match('/<[^<>]*$/' . $this->conf['modifier'],$preContent)>0 && preg_match('/^[^<>]*>/' . $this->conf['modifier'],$postContent)>0 ) {
				$inTag = TRUE;
			}
			if (!$inTag) {
				// support for joined words (with a dashes)
				$preMatch = '';
				$postMatch = '';
				if ($this->checkLocalGlobal($typeConfigArray,'checkPreAndPostMatches')>0) {
					preg_match('/(?<=\W)\w*-$/' . $this->conf['modifier'], $preContent, $preMatch);
					preg_match('/^-\w*(?=\W)/' . $this->conf['modifier'], $postContent, $postMatch);
				}
				$matchedTerm = $preMatch[0].$matchesArray[$i][0].$postMatch[0];
				$matchStart = $matchesArray[$i][1] - strlen($preMatch[0]);
				$matchEnd = $matchStart + strlen($matchedTerm);
				
				// check for nested matches
				$isNested = FALSE;
				$checkArray = $positionsArray;
				foreach ($checkArray as $start => $value) {
					$length = strlen($value['matchedTerm']);
					$end = $start+$length;
					if ( ($matchStart>=$start&&$matchStart<$end) || ($matchEnd>$start&&$matchEnd<=$end) ) {
						$isNested = TRUE;
					}
				}
				
				// change the sign of the matchStart if the matchedTerm is nested
				$matchStart = $isNested ? -$matchStart : $matchStart;
				$positionsArray[$matchStart] = array(
					'termKey' => $termKey,
					'matchedTerm' => $matchedTerm,
					'preMatch' => $preMatch[0],
					'postMatch' => $postMatch[0]
					);
			}
		}
	}
	
	function doReplace($content,$positionsArray) {
		$posStart = 0;
		$newContent = '';
		if($positionsArray){
			foreach ($positionsArray as $matchStart => $matchArray) {
				if ($matchStart>=0) { // ignore nested matches
					$matchLength = strlen($matchArray['matchedTerm']);
					$termKey = $matchArray['termKey'];
					$replacement = $this->getReplacement($termKey,$matchArray['matchedTerm'],$matchArray['preMatch'],$matchArray['postMatch']);
					$replacementLength = strlen($replacement);
					$newContent = $newContent.substr($content,$posStart,$matchStart-$posStart).$replacement;
					$posStart = $matchStart+$matchLength;
				}
			}
			$newContent = $newContent.substr($content,$posStart);
		} else {
			$newContent = $content;
		}
					
		return $newContent;
	}

	/**
	 * 	Do something with the matched term (replace, stdWrap, link, tag)
	 *
	 * @param int			$termKey: the internal "uid" of the term (not related to the database uid)
	 * @param string 		$matchedTerm: The matched term including pre and post matches
	 * @return string 		The replaced, linked and tagged term
	 * @author Jochen Rau
	 */
	function getReplacement($termKey,$matchedTerm,$preMatch,$postMatch) {
		$termArray = $this->termsArray[$termKey];
		$typeConfigArray = $this->typesArray[$termArray['term_type'] . '.'];
		// register the term array
		$this->registerFields($typeConfigArray,$termKey);
		
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

		// replace matched term
		if ( $this->checkLocalGlobal($typeConfigArray,'replaceTerm') && $termArray['term_replace'] ) {
			// if the first letter of the matched term is upper case
			// make the first letter of the replacing term also upper case
			// (\p{Lu} stands for "unicode letter uppercase")
			if ( preg_match('/^\p{Lu}/u',$matchedTerm)>0 ) {
				$matchedTerm = $preMatch . ucfirst($termArray['term_replace']) . $postMatch;
				// TODO ucfirst is not UTF8 safe; it depends on the locale settings (they could be ASCII)
			} else {
				$matchedTerm = $preMatch . $termArray['term_replace'] . $postMatch;
			}
		}

		$GLOBALS['TSFE']->register['contagged_matchedTerm'] = $matchedTerm;
		if ( !$termArray['exclude'] && !$typeConfigArray['dontListTerms'] ) {
			$GLOBALS['TSFE']->register['contagged_termsFound'][] = strip_tags($matchedTerm);
		}

		// call stdWrap to handle the matched term via TS BEFORE it is wraped with a-tags
		if ($typeConfigArray['preStdWrap.']) {
			$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['preStdWrap.']);
		}

		$matchedTerm = $this->linkMatchedTerm($matchedTerm,$typeConfigArray,$termKey);

		// call stdWrap to handle the matched term via TS AFTER it was wrapped with a-tags
		if ($typeConfigArray['postStdWrap.'] or $typeConfigArray['stdWrap.']) {
			$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['postStdWrap.']);
			$matchedTerm = $this->cObj->stdWrap($matchedTerm,$typeConfigArray['stdWrap.']); // for compatibility with < v0.0.5
		}

		if ( !empty($typeConfigArray['tag']) ) {
			$matchedTerm = $before . $matchedTerm . $after;
		}
		
		// TODO Edit Icons
		// $editIconsConf = array(
		// 	'styleAttribute' => '',
		// 	);
		$matchedTerm = $this->cObj->editIcons($matchedTerm,'tx_contagged_terms:sys_language_uid,hidden,starttime,endtime,fe_group,term_main,term_alt,term_type,term_lang,term_replace,desc_short,desc_long,link,exclude',$editIconsConf,'tx_contagged_terms:'.$termArray['uid'],NULL,'&defVals[tx_contagged_terms][desc_short]=TEST');
		
		return $matchedTerm;

	}

	/**
	 * Some content tagged by configured tags could be prevented from beeing parsed.
	 * This function collects all the tags which should be considered.
	 *
	 * @return	string		Comma separated list of tags
	 */
	function getTagsToOmitt() {
		$tagArray = array();

		// if there are tags to exclude: add them to the list
		if ($this->conf['excludeTags']) {
			$tagArray = t3lib_div::trimExplode(',',$this->conf['excludeTags'],1);
		}

		// if configured: add tags used by the term definitions
		if ($this->conf['autoExcludeTags']>0) {;
			foreach ($this->conf['types.'] as $key => $type) {
				if (!empty($type['tag']) && !in_array($type['tag'],$tagArray)) {
					$tagArray[] = $type['tag'];
				}
			}
		}
		
		$tagList = implode(',',$tagArray);

		return $tagList;
	}

	function insertKeywords() {
		$GLOBALS['TSFE']->register['contagged_termsFound'] = array_unique((array)$GLOBALS['TSFE']->register['contagged_termsFound']);
		// make a list of unique terms found in the content
		$termsFoundList = implode(',',$GLOBALS['TSFE']->register['contagged_termsFound']);
		// build an array to be passed to the UPDATE query
		$updateArray = array($this->prefixId . '_keywords' => $termsFoundList);
		// $updateArray = array('keywords' => $termsFoundList);
		// execute sql-query
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'pages', // TABLE ...
			'uid=' . $GLOBALS['TSFE']->id, // WHERE ...
			$updateArray
			);
	}
	
	/**
	 * Cleans up a string of keywords. Keywords at splitted by "," (comma)  ";" (semi colon) and linebreak
	 *
	 * @param	string		String of keywords
	 * @return	string		Cleaned up string, keywords will be separated by a comma only.
	 */
	function keywords($content)	{
		$listArr = split(',|;|'.chr(10),$content);
		reset($listArr);
		while(list($k,$v)=each($listArr))	{
			$listArr[$k]=trim($v);
		}
		return implode(',',$listArr);
	}
	
	/**
	 * Register the fields in $GLOBALS['TSFE] to be used in the TS Setup 
	 *
	 * @param	array		$typeConfigArray: Configuration array of the term
	 * @param	array		$this->termsArray: Array of terms
	 * @param	int			$termKey: Internal key of the term
	 */
	function registerFields($typeConfigArray,$termKey) {
		// Replace <p></p> with <br/>; Idea from Markus Timtner. Thank you!
		// TODO: strip or replace all block-tags
		if ($typeConfigArray['stripBlockTags']>0) {
			$this->termsArray[$termKey]['desc_long'] = preg_replace('/<p[^<>]*>(.*?)<\/p\s*>/' . $this->conf['modifier'],'$1<br />',$this->termsArray[$termKey]['desc_long']);
		}

		$GLOBALS['TSFE']->register['contagged_key'] = $termKey;

		// register all fields to be handled by the TS Setup
		foreach ($this->termsArray[$termKey] as $label => $value) {
			$GLOBALS['TSFE']->register['contagged_'.$label] = $value;
		}
	}

	/**
	 * Wrap the matched term in a link tag - as configured
	 *
	 * @param string $matchedTerm 
	 * @param string $typeConfigArray 
	 * @param string $this->termsArray 
	 * @param string $termKey 
	 * @return void
	 * @author Jochen Rau
	 */
	function linkMatchedTerm($matchedTerm,$typeConfigArray,$termKey) {
		$termArray = $this->termsArray[$termKey];

		// check conditions if the term should be linked to a list page
		$makeLink = $this->checkLocalGlobal($typeConfigArray,'linkToListPage');
		if ( $termArray['exclude']>0 ) {
			$makeLink = false;
		}
		if ($termArray['link']) {
			$makeLink = true;
		}

		// link the matched term to the front-end list page
		if ($makeLink) {
		    $cache = 0;
		    $this->pi_USER_INT_obj = 1;
		    $this->prefixId = 'tx_contagged_pi1';
		    $label = $matchedTerm;  // the link text
		    $overrulePIvars = array(
				'backPid' => $GLOBALS['TSFE']->id,
				'key' => $termKey,
			);
		    $clearAnyway=1;    // the current values of piVars will NOT be preserved
			if ($termArray['link']) {
				$altPageId = $termArray['link']; // ID of the target page
			} else {
				$altPageId = $termArray['listPages'][0];
			}
			$GLOBALS['TSFE']->register['contagged_list_page'] = $altPageId;
		    $matchedTerm = $this->pi_linkTP_keepPIvars($matchedTerm, $overrulePIvars, $cache, $clearAnyway, $altPageId);
			$this->prefixId = 'tx_contagged';
		}
		
		return $matchedTerm;
	}

	/**
	 * undocumented function
	 *
	 * @param string $typeConfigArray 
	 * @param string $attributeName 
	 * @return void
	 * @author Jochen Rau
	 */
	function checkLocalGlobal($typeConfigArray,$attributeName) {
		if ( isset($typeConfigArray[$attributeName]) ) {
			$addAttribute = ($typeConfigArray[$attributeName]>0) ? true : false;
		} else {
			$addAttribute = ($this->conf[$attributeName]>0) ? true : false;
		}

		return $addAttribute;
	}

	/**
	 * If the language of the term is undefined, or the page language is the same as language of the term,
	 * then the lang attribute will not be shown.
	 *
	 * If the terms language is defined and different from the page language, then the language attribute is added.
	 *
	 * @param string $typeConfigArray 
	 * @param string $termArray 
	 * @return void
	 * @author Jochen Rau
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

	/**
	 * undocumented function
	 *
	 * @param string $typeConfigArray 
	 * @param string $termArray 
	 * @return void
	 * @author Jochen Rau
	 */
	function getTitleAttribute($typeConfigArray,$termArray) {
		if ($this->checkLocalGlobal($typeConfigArray,'addTitleAttribute') && !empty($termArray['desc_short'])) {
			$titleAttribute = ' title="' . $termArray['desc_short'] . '"';
		}

		return $titleAttribute;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$typeConfigArray: ...
	 * @param	[type]		$termArray: ...
	 * @return	[type]		...
	 */
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
	 * @return	boolean	True if the page should be skipped
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
			if (t3lib_div::inArray((array)$pageUidsInRootline, $includeRootPageUid))
				$result = false;
		}
		$excludeRootPagesUids = t3lib_div::trimExplode(',', $this->conf['excludeRootPages'], 1);
		foreach ($excludeRootPagesUids as $excludeRootPageUid) {
			if (t3lib_div::inArray((array)$pageUidsInRootline, $excludeRootPageUid))
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