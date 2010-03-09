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
	var $typolinkConf;

	/**
	 * The main method. It instantly delegates the process to the parse function.
	 *
	 * @param	string		$content: The content
	 * @param	array		$conf: The configuration array
	 * @return	string		The parsed and tagged content that is displayed on the website
	 */
	public function main($content, $conf = NULL) {
		return $this->parse($content, $conf);
	}
	
	/**
	 * This method is to parse, tag and link specific terms in the given content.
	 *
	 * @param	string		$content: The content
	 * @param	array		$conf: The configuration array
	 * @return	string		The parsed and tagged content that is displayed on the website
	 */
	public function parse($content, $conf = NULL) {
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];
		$this->pi_setPiVarDefaults();
		if (!is_object($this->cObj)) {
			$this->cObj = t3lib_div::makeInstance('tslib_cObj');
			$this->cObj->setCurrentVal($GLOBALS['TSFE']->id);
		}

		$this->typolinkConf = is_array($this->conf['typolink.']) ? $this->conf['typolink.'] : array();
		if (!empty($this->typolinkConf['additionalParams'])) {
			$this->typolinkConf['additionalParams'] = $this->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
			unset($this->typolinkConf['additionalParams.']);
		}
		$this->typolinkConf['useCacheHash'] = 1;

		// exit if the content should be skipped
		if ($this->isContentToSkip()) return $content;		
		
		// get an array of all type configurations
		$this->typesArray = $this->conf['types.'];

		// get the model (an associated array of terms)
		$model = t3lib_div::makeInstance('tx_contagged_model_terms', $this);
		$this->termsArray = $model->findAllTerms();

		$sortedTerms = array();
		foreach ($this->termsArray as $termKey => $termArray) {
			$sortedTerms[] = array('term' => $termArray['term_main'], 'key' => $termKey);
			if (is_array($termArray['term_alt'])) {
				foreach ($termArray['term_alt'] as $term) {
					$sortedTerms[] = array('term' => $term, 'key' => $termKey);
				}
			}
		}

		// get a comma separated list of all tags which should be omitted
		$tagsToOmitt = $this->getTagsToOmitt();

		// TODO split recursively
		$parseObj = t3lib_div::makeInstance('t3lib_parsehtml');
		$splittedContent = $parseObj->splitIntoBlock($tagsToOmitt,$content);
		foreach((array)$splittedContent as $intKey => $HTMLvalue) {
			if (!($intKey%2)) {
				$positionsArray = array();
					foreach ($sortedTerms as $termAndKey) {
						if (empty($termAndKey['term'])) {
							continue;
						}
						$this->getPositions($splittedContent[$intKey],$positionsArray,$termAndKey['term'],$termAndKey['key']);
					}
				ksort($positionsArray);
				$splittedContent[$intKey] = $this->doReplace($splittedContent[$intKey],$positionsArray);			
			}
		}
		$parsedContent = implode('',$splittedContent);
		
		// update the keywords (field "tx_contagged_keywords" in table "page")
		if ($this->conf['updateKeywords'] > 0) {
			$this->updatePageKeywords();
		}
		$this->addJavaScript();

		return $parsedContent;
	}
	
	function getPositions($content,&$positionsArray,$term,$termKey) {
		$termArray = $this->termsArray[$termKey];
		$typeConfigArray = $this->typesArray[$termArray['term_type'] . '.'];
		// $regEx = $regEx = '/(?<=\P{L}|^)' . preg_quote($term,'/') . '(?=\P{L}|$)/' . $this->conf['modifier'];//$this->getRegEx($term,$termKey);
		if ($typeConfigArray['termIsRegEx'] > 0) {
			$regEx = $termArray['term_main'].$this->conf['modifier'];
		} else {
			// if (strstr($this->conf['modifier'], 'u') !== FALSE) {
				$regEx = '/(?<=\P{L}|^)' . preg_quote($term,'/') . '(?=\P{L}|$)/' . $this->conf['modifier'];
			// } else {
			// 	$regEx = '/(?<=\W|^)' . preg_quote($term,'/') . '(?=\W|$)/' . $this->conf['modifier'];
			// }
		}
		preg_match_all($regEx,$content,$matchesArray,PREG_OFFSET_CAPTURE);
		$matchesArray = $matchesArray[0]; // only take the full pattern matches of the regEx

		// determine the maximum of recurrences of the same term to be tagged
		$maxRecurrences = (!empty($this->conf['maxRecurrences'])) ? min($this->conf['maxRecurrences'], count($matchesArray)) : count($matchesArray);
		$step = $maxRecurrences != 0 ? ceil(count($matchesArray) / $maxRecurrences) : 1;
		for ($i=0; $i < count($matchesArray); $i = $i + $step) {
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
					if (strstr($this->conf['modifier'], 'u') !== FALSE) {
						preg_match('/(?<=\P{L})\p{L}*-$/' . $this->conf['modifier'], $preContent, $preMatch);
						preg_match('/^-\p{L}*(?=\P{L})/' . $this->conf['modifier'], $postContent, $postMatch);
					} else {
						preg_match('/(?<=\W)\w*-$/' . $this->conf['modifier'], $preContent, $preMatch);
						preg_match('/^-\w*(?=\W)/' . $this->conf['modifier'], $postContent, $postMatch);
					}
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
					if ( (($matchStart >= $start) && ($matchStart < $end)) || (($matchEnd > $start) && ($matchEnd <= $end)) ) {
						$isNested = TRUE;
					}
				}				
				if (!$isNested) {
					$positionsArray[$matchStart] = array(
						'termKey' => $termKey,
						'matchedTerm' => $matchedTerm,
						'preMatch' => $preMatch[0],
						'postMatch' => $postMatch[0]
						);
				}	
			}
		}
	}
	
	function doReplace($content,$positionsArray) {
		$posStart = 0;
		$newContent = '';
		if($positionsArray){
			foreach ($positionsArray as $matchStart => $matchArray) {
				$matchLength = strlen($matchArray['matchedTerm']);
				$termKey = $matchArray['termKey'];
				$replacement = $this->getReplacement($termKey, $matchArray['matchedTerm'], $matchArray['preMatch'], $matchArray['postMatch']);
				$replacementLength = strlen($replacement);
				$newContent = $newContent.substr($content,$posStart,$matchStart-$posStart).$replacement;
				$posStart = $matchStart + $matchLength;
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
			$GLOBALS['TSFE']->register['contagged_matchedTerm'] = $termArray['term_replace'];
			$this->updateIndex($termKey, $termArray['term_replace']);
			if (preg_match('/^\p{Lu}/u', $matchedTerm) > 0 && ($this->checkLocalGlobal($typeConfigArray, 'respectCase') > 0)) {
				$matchedTerm = $preMatch . ucfirst($termArray['term_replace']) . $postMatch;
				// TODO ucfirst is not UTF8 safe; it depends on the locale settings (they could be ASCII)
			} else {
				$matchedTerm = $preMatch . $termArray['term_replace'] . $postMatch;
			}
		} else {
			$GLOBALS['TSFE']->register['contagged_matchedTerm'] = $matchedTerm;
			$this->updateIndex($termKey, $termArray['term']);
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
		$editIconsConf = array(
			'styleAttribute' => '',
			);
		$matchedTerm = $this->cObj->editIcons($matchedTerm,'tx_contagged_terms:sys_language_uid,hidden,starttime,endtime,fe_group,term_main,term_alt,term_type,term_lang,term_replace,desc_short,desc_long,image,dam_images,imagecaption,imagealt,imagetitle,related,link,exclude',$editIconsConf,'tx_contagged_terms:'.$termArray['uid'],NULL,'&defVals[tx_contagged_terms][desc_short]=TEST');
		
		return $matchedTerm;
	}


	function updateIndex($termKey, $matchedTerm) {
		$currentRecord = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
		$GLOBALS['T3_VAR']['ext']['contagged']['index'][$GLOBALS['TSFE']->id][$termKey] = array(
			'matchedTerm' => $matchedTerm,
			'source' => $this->termsArray[$termKey]['source'],
			'uid' => $this->termsArray[$termKey]['uid'],
			'currentRecordSource' => $currentRecord[0],
			'currentRecordUid' => $currentRecord[1],
			'currentPid' => $GLOBALS['TSFE']->id
			);
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

	function updatePageKeywords() {
		$terms = array();
		if (is_array($GLOBALS['T3_VAR']['ext']['contagged']['index'][$GLOBALS['TSFE']->id])) {
			foreach ($GLOBALS['T3_VAR']['ext']['contagged']['index'][$GLOBALS['TSFE']->id] as $termKey => $indexArray) {
				$terms[] = $indexArray['matchedTerm'];
			}
		}
		$termsList = implode(',', $terms);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'pages', // TABLE ...
			'uid=' . $GLOBALS['TSFE']->id, // WHERE ...
			array($this->prefixId . '_keywords' => $termsList)
			);
	}
	
	/**
	 * Register the fields in $GLOBALS['TSFE] to be used in the TS Setup 
	 *
	 * @param	array		$typeConfigArray: Configuration array of the term
	 * @param	array		$this->termsArray: Array of terms
	 * @param	int			$termKey: Internal key of the term
	 */
	function registerFields($typeConfigArray,$termKey) {
		if ($typeConfigArray['stripBlockTags']>0) {
			$this->termsArray[$termKey]['desc_short_inline'] = $this->stripBlockTags($this->termsArray[$termKey]['desc_short']);
			$text = $this->cObj->parseFunc($this->termsArray[$termKey]['desc_long'], array(), '< lib.parseFunc_RTE');
			$this->termsArray[$termKey]['desc_long_inline'] = $this->stripBlockTags($text);
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
			unset($typolinkConf);
			$typolinkConf = $this->typolinkConf;
			if (!empty($typeConfigArray['typolink.'])) {
				$typolinkConf = t3lib_div::array_merge_recursive_overrule($typolinkConf, $typeConfigArray['typolink.']);
			}
			if ($termArray['link']) {
				$typolinkConf['parameter'] = $termArray['link'];
				$typolinkConf['additionalParams'] = $termArray['link.']['additionalParams'];
			} else {
				if ($typeConfigArray['listPages']) {
					$typolinkConf['parameter'] = array_shift(t3lib_div::trimExplode(',',$typeConfigArray['listPages'],1));
				} else {
					$typolinkConf['parameter'] = array_shift(t3lib_div::trimExplode(',',$this->conf['listPages'],1));
				}
				$GLOBALS['TSFE']->register['contagged_list_page'] = $typolinkConf['parameter'];
				$additionalParams['source'] = $termArray['source'];
				$additionalParams['uid'] = $termArray['uid'];
				if ($this->checkLocalGlobal($typeConfigArray,'addBackLink')) {
					$additionalParams['backPid'] = $GLOBALS['TSFE']->id;
				}
				$typolinkConf['additionalParams'] = t3lib_div::implodeArrayForUrl('tx_contagged', $additionalParams, '', 1);
			}
			$GLOBALS['TSFE']->register['contagged_link_url'] = $this->cObj->typoLink_URL($typolinkConf);
			$matchedTerm = $this->cObj->typolink($matchedTerm, $typolinkConf);		
		}
		
		return $matchedTerm;
	}

	/**
	 * Overwrite global settings with settings of the type configuration.
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
	 * Renders the title attribute of the tag.
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
	 * Renders the class attribute of the tag.
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
		if (!empty($this->cObj)) {
			if ($this->cObj->getFieldVal('tx_contagged_dont_parse') == 1) {
				$result = true;
			}
		}

		return $result;
	}
	
	/**
	 * Replaces block elements with inline versions (if possible)
	 * 
	 * @param string $text 
	 * @return string The reformatted text
	 */
	protected function stripBlockTags($text) {
		$blockElements = 'address|blockquote|center|del|dir|div|dl|fieldset|form|h[1-6]|hr|ins|isindex|menu|noframes|noscript|ol|p|pre|table|ul|center|dir|isindex|menu|noframes';
	    $text = preg_replace('%' . $this->getOpeningTag('li|dd') . '%xs', '&nbsp;&nbsp;*&nbsp;', $text);
	    $text = preg_replace('%' . $this->getClosingTag('li|dt') . '%xs', '<br />', $text);
	    $text = preg_replace('%' . $this->getClosingTag('ol|ul|dl') . '%xs', '', $text);
	    $text = preg_replace('%' . $this->getOpeningTag($blockElements) . '%xs', '', $text);
	    $text = preg_replace('%' . $this->getClosingTag($blockElements) . '%xs', '<br />', $text);
	    $text = preg_replace('%' . $this->getOpeningTag('br') . '{2,2}%xs', '<br />', $text);
	    return $text;
	}
	
	/**
	 * Returns an opening tag of the allowed elements.
	 *
	 * @param string $allowedElements The allowed elements ("a|b|c")
	 * @return void
	 */
	protected function getOpeningTag($allowedElements) {
		$tag = "
			(
				<(?:" . $allowedElements . ")		# opening tag ('<tag') or closing tag ('</tag')
				(?:
					(?:
						\s+\w+					# EITHER spaces, followed by word characters (attribute names)
						(?:
							\s*=?\s*			# equals
							(?>
								\".*?\"			# attribute values in double-quotes
								|
								'.*?'			# attribute values in single-quotes
								|
								[^'\">\s]+		# plain attribute values
							)
						)?
					)+\s*
					|							# OR only spaces
					\s*
				)
				/?>								# closing the tag with '>' or '/>'
			)";
		return $tag;		
	}

	/**
	 * Returns a closing tag of the allowed elements.
	 *
	 * @param string $allowedElements The allowed elements ("a|b|c")
	 * @return void
	 */
	protected function getClosingTag($allowedElements) {
		$tag = "
			(
				</(?:" . $allowedElements . ")		# opening tag ('<tag') or closing tag ('</tag')
				(?:
					(?:
						\s+\w+					# EITHER spaces, followed by word characters (attribute names)
						(?:
							\s*=?\s*			# equals
							(?>
								\".*?\"			# attribute values in double-quotes
								|
								'.*?'			# attribute values in single-quotes
								|
								[^'\">\s]+		# plain attribute values
							)
						)?
					)+\s*
					|							# OR only spaces
					\s*
				)
				>								# closing the tag with '>' or '/>'
			)";
		return $tag;		
	}
	
	/**
	 * Adds the qTip plugin script (jQuery). You can call this function in you TS setup if necessary.
	 *
	 * @return void
	 */
	protected function addJavaScript() {
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contagged']);
		$javaScriptPathAndFilename = $extensionConfiguration['javaScriptPathAndFilename'];
		if (is_string($javaScriptPathAndFilename) && $javaScriptPathAndFilename !== '') {
			$GLOBALS['TSFE']->additionalHeaderData['contagged'] .= '<script src="' . $javaScriptPathAndFilename . '" type="text/javascript"></script>';
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/class.tx_contagged.php']);
}
?>