<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2007 Jochen Rau <j.rau@web.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * contagged list plugin
 *
 * @author    Jochen Rau <j.rau@web.de>
 * @package    TYPO3
 * @subpackage    tx_contagged_pi1
 */
class tx_contagged_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	var $prefixId = 'tx_contagged'; // same as class name
	var $scriptRelPath = 'pi1/class.tx_contagged_pi1.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $templateFile = 'EXT:contagged/pi1/contagged.tmpl';

	var $conf; // the TypoScript configuration array
	var $templateCode; // template file
	var $local_cObj;

	var $typolinkConf;

	var $backPid; // pid of the last visited page (from piVars)
	var $indexChar; // char of the given index the user has clicked on (from piVars)

	/**
	 * main method of the contagged list plugin
	 *
	 * @param    string        $content: The content of the cObj
	 * @param    array        $conf: The configuration
	 * @return    string            a single or list view of terms
	 */
	function main($content, $conf) {
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.'];
		$this->parser = GeneralUtility::makeInstance('tx_contagged');
		$this->local_cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
		if (is_array($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_contagged.'])) {
			$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_contagged.'];
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->conf, $conf);
		}
		$this->pi_loadLL();
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile'] ? $this->conf['templateFile'] : $this->templateFile);
		$this->typolinkConf = is_array($this->conf['typolink.']) ? $this->conf['typolink.'] : array();
		$this->typolinkConf['parameter.']['current'] = 1;
		if (!empty($this->typolinkConf['additionalParams'])) {
			$this->typolinkConf['additionalParams'] = $this->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
			unset($this->typolinkConf['additionalParams.']);
		}
		$this->typolinkConf['useCacheHash'] = 1;
		$this->backPid = $this->piVars['backPid'] ? intval($this->piVars['backPid']) : NULL;
		$this->pointer = $this->piVars['pointer'] ? intval($this->piVars['pointer']) : NULL;
		$this->indexChar = $this->piVars['index'] ? urldecode($this->piVars['index']) : NULL; // TODO The length should be configurable
		if (!is_null($this->piVars['source']) && !is_null($this->piVars['uid'])) {
			$dataSource = stripslashes($this->piVars['source']);
			$uid = intval($this->piVars['uid']);
			$termKey = stripslashes($this->piVars['source']) . '_' . intval($this->piVars['uid']);
		}
		$sword = $this->piVars['sword'] ? htmlspecialchars(urldecode($this->piVars['sword'])) : NULL;

		// get an array of all type configurations
		$this->typesArray = $this->conf['types.'];

		// get the model (an associated array of terms)
		$this->mapper = GeneralUtility::makeInstance('tx_contagged_model_mapper', $this);
		$this->model = GeneralUtility::makeInstance('tx_contagged_model_terms', $this);

		if (!is_null($termKey)) {
			$content .= $this->renderSingleItemByKey($dataSource, $uid);
		} elseif ((strtolower($this->conf['layout']) == 'minilist') || (strtolower($this->cObj->data['select_key']) == 'minilist')) {
			$content .= $this->renderMiniList();
		} elseif (is_null($termKey) && is_null($sword)) {
			$content .= $this->renderList();
		} elseif (is_null($termKey) && !is_null($sword)) {
			$content .= $this->renderListBySword($sword);
		}

		// TODO hook "newRenderFunctionName"

		$content = $this->removeUnfilledMarker($content);

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Renders the list of terms
	 *
	 * @return    $string    The list as HTML
	 */
	function renderList() {
		$markerArray = array();
		$wrappedSubpartArray = array();
		$subparts = $this->getSubparts('LIST');
		$termsArray = $this->model->findAllTermsToListOnPage();
		$this->renderLinks($markerArray, $wrappedSubpartArray);
		$this->renderIndex($markerArray, $termsArray);
		$this->renderSearchBox($markerArray);
		$indexedTerms = array();
		foreach ($termsArray as $termKey => $termArray) {
			if ($this->indexChar == NULL || $termArray['indexChar'] == $this->indexChar) {
				$indexedTerms[$termKey] = $termArray;
			}
		}
		if ($this->conf['pagebrowser.']['enable'] > 0) {
			$this->renderPageBrowser($markerArray, count($indexedTerms));
			$terms = array_slice($indexedTerms, ($this->pointer * $this->internal['results_at_a_time']), $this->internal['results_at_a_time'], TRUE);
		} else {
			$terms = $indexedTerms;
		}
		foreach ($terms as $termKey => $termArray) {
			$this->renderSingleItem($termArray, $markerArray, $wrappedSubpartArray);
			$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray, $subpartArray, $wrappedSubpartArray);
		}
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'], $markerArray, $subpartArray, $wrappedSubpartArray);

		return $content;
	}

	/**
	 * Renders the mini list of terms
	 *
	 * @return    $string    The list as HTML
	 */
	function renderMiniList() {
		$subparts = $this->getSubparts('MINILIST');
		$terms = $this->model->findAllTermsToListOnPage();
		foreach ($terms as $termKey => $termArray) {
			$this->renderSingleItem($termArray, $markerArray, $wrappedSubpartArray);
			$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray, $subpartArray, $wrappedSubpartArray);
		}
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'], $markerArray, $subpartArray, $wrappedSubpartArray);

		return $content;
	}

	function renderListBySword($sword) {
		$markerArray = array();
		$wrappedSubpartArray = array();
		$swordMatched = FALSE;
		$subparts = $this->getSubparts('LIST');
		$termsArray = $this->model->findAllTermsToListOnPage();
		$this->renderLinks($markerArray, $wrappedSubpartArray);
		$this->renderIndex($markerArray, $termsArray);
		$this->renderSearchBox($markerArray);
		foreach ($termsArray as $termKey => $termArray) {
			$fieldsToSearch = GeneralUtility::trimExplode(',', $this->conf['searchbox.']['fieldsToSearch']);
			foreach ($fieldsToSearch as $field) {
				if (is_array($termArray[$field])) {
					foreach ($termArray[$field] as $subFieldValue) {
						if (preg_match('/' . preg_quote($sword, '/') . '/Uis', strip_tags($subFieldValue)) > 0) {
							$swordMatched = TRUE;
							break;
						}
					}
				} else {
					if (preg_match('/' . preg_quote($sword, '/') . '/Uis', strip_tags($termArray[$field])) > 0) {
						$swordMatched = TRUE;
						break;
					}
				}
			}
			if ($swordMatched) {
				$this->renderSingleItem($termArray, $markerArray, $wrappedSubpartArray);
				$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray, $subpartArray, $wrappedSubpartArray);
				$swordMatched = FALSE;
			}
		}
		if ($subpartArray['###LIST###'] == '') {
			$subpartArray['###LIST###'] = $this->pi_getLL('no_matches');
		}

		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'], $markerArray, $subpartArray, $wrappedSubpartArray);

		return $content;
	}

	function renderSingleItemByKey($dataSource, $uid) {
		$markerArray = array();
		$wrappedSubpartArray = array();
		$termArray = $this->model->findTermByUid($dataSource, $uid);
		$subparts = $this->getSubparts('SINGLE');
		$this->renderLinks($markerArray, $wrappedSubpartArray);
		$termsArray = $this->model->findAllTermsToListOnPage();
		$this->renderIndex($markerArray, $termsArray);
		$this->renderSingleItem($termArray, $markerArray, $wrappedSubpartArray);
		$subpartArray['###LIST###'] = $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray, $subpartArray, $wrappedSubpartArray);
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'], $markerArray, $subpartArray, $wrappedSubpartArray);

		return $content;
	}

	// TODO hook "newRenderFunction"

	function getSubparts($templateName = 'LIST') {
		$subparts['template_list'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE_' . $templateName . '###');
		$subparts['item'] = $this->cObj->getSubpart($subparts['template_list'], '###ITEM###');

		return $subparts;
	}

	function renderLinks(&$markerArray, &$wrappedSubpartArray) {
		// make "back to..." link
		if ($this->backPid && $this->conf['addBackLink'] !== '0') {
			if ($this->conf['addBackLinkDescription'] > 0) {
				$pageSelectObject = new \TYPO3\CMS\Frontend\Page\PageRepository;
				$pageSelectObject->init(FALSE);
				$pageSelectObject->sys_language_uid = $GLOBALS['TSFE']->sys_language_uid;
				$backPage = $pageSelectObject->getPage($this->backPid);
				$markerArray['###BACK_TO###'] = $this->pi_getLL('backToPage') . " \"" . $backPage['title'] . "\"";
			} else {
				$markerArray['###BACK_TO###'] = $this->pi_getLL('back');
			}
			unset($typolinkConf);
			$typolinkConf['parameter'] = $this->backPid;
			$wrappedSubpartArray['###LINK_BACK_TO###'] = $this->local_cObj->typolinkWrap($typolinkConf);
		} else {
			$markerArray['###LINK_BACK_TO###'] = '';
		}

		// make "link to all entries"
		$markerArray['###INDEX_ALL###'] = $this->pi_linkTP($this->pi_getLL('all'));

		// make "to list ..." link
		unset($typolinkConf);
		$markerArray['###TO_LIST###'] = $this->pi_getLL('toList');
		$typolinkConf = $this->typolinkConf;
		$typolinkConf['parameter.']['wrap'] = "|," . $GLOBALS['TSFE']->type;
		$wrappedSubpartArray['###LINK_TO_LIST###'] = $this->local_cObj->typolinkWrap($typolinkConf);
	}

	function renderSingleItem($termArray, &$markerArray, &$wrappedSubpartArray) {
		$typeConfigArray = $this->conf['types.'][$termArray['term_type'] . '.'];

		$termArray['desc_long'] = $this->cObj->parseFunc($termArray['desc_long'], array(), '< lib.parseFunc_RTE');
		if (!empty($this->conf['fieldsToParse'])) {
			$fieldsToParse = GeneralUtility::trimExplode(',', $this->conf['fieldsToParse']);
			$excludeTerms = $termArray['term_alt'];
			$excludeTerms[] = $termArray['term_main'];
			foreach ($fieldsToParse as $fieldName) {
				$termArray[$fieldName] = $this->parser->parse($termArray[$fieldName], array('excludeTerms' => implode(',', $excludeTerms)));
			}
		}

		$markerArray['###TERM_TYPE###'] = $typeConfigArray['label'];
		$markerArray['###TERM###'] = $termArray['term'];
		$editIconsConf = array(
			'styleAttribute' => '',
		);
		$markerArray['###TERM_KEY###'] = $termArray['source'] . '_' . $termArray['uid'];
		$markerArray['###TERM###'] = $this->cObj->editIcons($termArray['term'], 'tx_contagged_terms:term_main,term_alt,term_type,term_lang,term_replace,desc_short,desc_long,image,dam_images,imagecaption,imagealt,imagetitle,related,link,exclude', $editIconsConf, 'tx_contagged_terms:' . $termArray['uid']);
		$markerArray['###TERM_MAIN###'] = $termArray['term_main'];
		$markerArray['###TERM_ALT###'] = $termArray['term_alt'] ? implode(', ', $termArray['term_alt']) : $this->pi_getLL('na');
		$markerArray['###TERM_REPLACE###'] = $termArray['term_replace'] ? $termArray['term_replace'] : $this->pi_getLL('na');
		$markerArray['###DESC_SHORT###'] = $termArray['desc_short'] ? $termArray['desc_short'] : $this->pi_getLL('na');
		$markerArray['###DESC_LONG###'] = $termArray['desc_long'] ? $termArray['desc_long'] : $this->pi_getLL('na');
		$markerArray['###REFERENCE###'] = $termArray['reference'] ? $termArray['reference'] : $this->pi_getLL('na');
		$markerArray['###PRONUNCIATION###'] = $termArray['pronunciation'] ? $termArray['pronunciation'] : $this->pi_getLL('na');
		$markerArray['###IMAGES###'] = $this->renderImages($termArray);
		$multimediaConfiguration = $this->conf['multimedia.'];
		$multimediaConfiguration['file'] = $termArray['multimedia'];
		$markerArray['###MULTIMEDIA###'] = $this->cObj->cObjGetSingle('MULTIMEDIA', $multimediaConfiguration);
		$markerArray['###RELATED###'] = $this->renderRelated($termArray);
		$markerArray['###TERM_LANG###'] = $this->pi_getLL('lang.' . $termArray['term_lang']) ? $this->pi_getLL('lang.' . $termArray['term_lang']) : $this->pi_getLL('na');

		$labelWrap = array();
		$labelWrap['wrap'] = $typeConfigArray['labelWrap1'] ? $typeConfigArray['labelWrap1'] : $this->conf['labelWrap1'];
		$markerArray['###TERM_TYPE_LABEL###'] = $markerArray['###TERM_TYPE###'] ? $this->local_cObj->stdWrap($this->pi_getLL('term_type'), $labelWrap) : '';
		$markerArray['###TERM_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term'), $labelWrap);
		$markerArray['###TERM_MAIN_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term_main'), $labelWrap);
		$markerArray['###TERM_ALT_LABEL###'] = $markerArray['###TERM_ALT###'] ? $this->local_cObj->stdWrap($this->pi_getLL('term_alt'), $labelWrap) : '';
		$markerArray['###TERM_REPLACE_LABEL###'] = $markerArray['###TERM_REPLACE###'] ? $this->local_cObj->stdWrap($this->pi_getLL('term_replace'), $labelWrap) : '';
		$markerArray['###DESC_SHORT_LABEL###'] = $markerArray['###DESC_SHORT###'] ? $this->local_cObj->stdWrap($this->pi_getLL('desc_short'), $labelWrap) : '';
		$markerArray['###DESC_LONG_LABEL###'] = $markerArray['###DESC_LONG###'] ? $this->local_cObj->stdWrap($this->pi_getLL('desc_long'), $labelWrap) : '';
		$markerArray['###REFERENCE_LABEL###'] = $markerArray['###REFERENCE###'] ? $this->local_cObj->stdWrap($this->pi_getLL('reference'), $labelWrap) : '';
		$markerArray['###PRONUNCIATION_LABEL###'] = $markerArray['###PRONUNCIATION###'] ? $this->local_cObj->stdWrap($this->pi_getLL('pronunciation'), $labelWrap) : '';
		$markerArray['###MULTIMEDIA_LABEL###'] = $markerArray['###MULTIMEDIA###'] ? $this->local_cObj->stdWrap($this->pi_getLL('multimedia'), $labelWrap) : '';
		$markerArray['###RELATED_LABEL###'] = $markerArray['###RELATED###'] ? $this->local_cObj->stdWrap($this->pi_getLL('related'), $labelWrap) : '';
		$markerArray['###IMAGES_LABEL###'] = $markerArray['###IMAGES###'] ? $this->local_cObj->stdWrap($this->pi_getLL('images'), $labelWrap) : '';
		$markerArray['###TERM_LANG_LABEL###'] = $markerArray['###TERM_LANG###'] ? $this->local_cObj->stdWrap($this->pi_getLL('term_lang'), $labelWrap) : '';

		// make "more..." link
		$markerArray['###DETAILS###'] = $this->pi_getLL('details');
		$typolinkConf = $this->typolinkConf;
		if (!empty($typeConfigArray['typolink.'])) {
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($typolinkConf, $typeConfigArray['typolink.']);
		}
		$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[source]=' . $termArray['source'] . '&' . $this->prefixId . '[uid]=' . $termArray['uid'];
		$typolinkConf['parameter'] = array_shift($this->model->getListPidsArray($termArray['term_type']));
		$this->typolinkConf['parameter.']['current'] = 0;
		$typolinkConf['parameter.']['wrap'] = "|," . $GLOBALS['TSFE']->type;
		$wrappedSubpartArray['###LINK_DETAILS###'] = $this->local_cObj->typolinkWrap($typolinkConf);
	}

	function renderRelated($term) {
		$relatedCode = '';
		if (is_array($term['related'])) {
			foreach ($term['related'] as $termReference) {
				$relatedTerm = $this->model->findTermByUid($termReference['source'], $termReference['uid']);
				$typolinkConf = $this->typolinkConf;
				if (!empty($typeConfigArray['typolink.'])) {
					\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($typolinkConf, $typeConfigArray['typolink.']);
				}
				$typolinkConf['useCacheHash'] = 1;
				$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[source]=' . $termReference['source'] . '&' . $this->prefixId . '[uid]=' . $termReference['uid'];
				$typolinkConf['parameter.']['wrap'] = "|," . $GLOBALS['TSFE']->type;
				$relatedCode .= $this->local_cObj->stdWrap($this->local_cObj->typoLink($relatedTerm['term'], $typolinkConf), $this->conf['related.']['single.']['stdWrap.']);
			}
			return $this->local_cObj->stdWrap(trim($relatedCode), $this->conf['related.']['stdWrap.']);
		} else {
			return NULL;
		}
	}

	function renderImages($termArray) {
		$images = array();
		$imagesCaption = array();
		$imagesAltText = array();
		$imagesTitleText = array();
		$imagesCode = '';
		$imagesConf = $this->conf['images.']['single.'];
		$extConfArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contagged']);
		if ($extConfArray['getImagesFromDAM'] > 0 && t3lib_extMgm::isLoaded('dam')) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dam.file_path, tx_dam.file_name, tx_dam.alt_text, tx_dam.caption, tx_dam.title',
				'tx_dam', 'tx_dam_mm_ref', 'tx_contagged_terms',
				'AND tx_dam_mm_ref.tablenames = "tx_contagged_terms" AND tx_dam_mm_ref.ident="dam_images" ' .
					'AND tx_dam_mm_ref.uid_foreign = "' . $termArray['uid'] . '"', '', 'tx_dam_mm_ref.sorting_foreign ASC'
			);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$images[] = $row['file_path'] . $row['file_name'];
				$imagesCaption[] = str_replace(array(chr(10), chr(13)), ' ', $row['caption'] . ' ');
				$imagesAltText[] = str_replace(array(chr(10), chr(13)), ' ', $row['alt_text'] . ' ');
				$imagesTitleText[] = str_replace(array(chr(10), chr(13)), ' ', $row['title'] . ' ');
			}
		} else {
			$images = GeneralUtility::trimExplode(',', $termArray['image'], 1);
			$imagesWithPath = array();
			foreach ($images as $image) {
				$imagesWithPath[] = 'uploads/pics/' . $image;
			}
			$images = $imagesWithPath;
			$imagesCaption = GeneralUtility::trimExplode(chr(10), $termArray['imagecaption']);
			$imagesAltText = GeneralUtility::trimExplode(chr(10), $termArray['imagealt']);
			$imagesTitleText = GeneralUtility::trimExplode(chr(10), $termArray['imagetitle']);
		}

		if (!empty($images)) {
			foreach ($images as $key => $image) {
				$imagesConf['image.']['file'] = $image;
				$imagesConf['image.']['altText'] = $imagesAltText[$key];
				$imagesConf['image.']['titleText'] = $imagesTitleText[$key];
				$caption = $imagesCaption[$key] != '' ? $this->local_cObj->stdWrap($imagesCaption[$key], $this->conf['images.']['caption.']['stdWrap.']) : '';
				$imagesCode .= $this->local_cObj->IMAGE($imagesConf['image.']);
				$imagesCode .= $caption;
			}
			return $this->local_cObj->stdWrap(trim($imagesCode), $this->conf['images.']['stdWrap.']);
		} else {
			return NULL;
		}
	}

	function renderIndex(&$markerArray, &$terms) {
		if ($this->conf['index.']['enable'] > 0) {
			$subparts = array();
			$subparts['template_index'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE_INDEX###');
			$subparts['item'] = $this->cObj->getSubpart($subparts['template_index'], '###ITEM###');

			$indexArray = $this->getIndexArray($terms);

			// wrap index chars and add a class attribute if there is a selected index char.
			foreach ($indexArray as $indexChar => $link) {
				$cssClass = '';
				if ($this->piVars['index'] == $indexChar) {
					$cssClass = " class='tx-contagged-act'";
				}
				if ($link) {
					$markerArray['###SINGLE_CHAR###'] = '<span' . $cssClass . '>' . $link . '</span>';
				} elseif ($this->conf['index.']['showOnlyMatchedIndexChars'] == 0) {
					$markerArray['###SINGLE_CHAR###'] = '<span' . $cssClass . '>' . $indexChar . '</span>';
				} else {
					$markerArray['###SINGLE_CHAR###'] = '';
				}
				$subpartArray['###INDEX_CONTENT###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray);
			}
			$markerArray['###INDEX###'] = $this->cObj->substituteMarkerArrayCached($subparts['template_index'], $markerArray, $subpartArray);
		} else {
			$markerArray['###INDEX###'] = '';
		}
	}

	function getIndexArray(&$terms) {
		$indexArray = array();
		$reverseIndexArray = array();
		// Get localized index chars.
		foreach (GeneralUtility::trimExplode(',', $this->pi_getLL('indexChars')) as $key => $value) {
			$subCharArray = GeneralUtility::trimExplode('|', $value);
			$indexArray[$subCharArray[0]] = NULL;
			foreach ($subCharArray as $subChar) {
				$reverseIndexArray[$subChar] = $subCharArray[0];
			}
		}

		// The configuered subchars like Ö will be linked as O (see documentation and file "locallang.xml").
		$typolinkConf = $this->typolinkConf;
		foreach ($terms as $termKey => $termArray) {
			if ($this->conf['types.'][$termArray['term_type'] . '.']['dontListTerms'] != 1) {
				foreach ($reverseIndexArray as $subChar => $indexChar) {
					if (preg_match('/^' . preg_quote($subChar) . '/' . $this->conf['modifier'], $termArray['term']) > 0) {
						$typolinkConf['additionalParams'] = '&' . $this->prefixId . '[index]=' . $indexChar;
						$indexArray[$indexChar] = $this->local_cObj->typolink($indexChar, $typolinkConf);
						$terms[$termKey]['indexChar'] = $indexChar;
					}
				}
				// If the term matches no given index char, crate one if desired and add it to the index
				if (($terms[$termKey]['indexChar'] == '') && ($this->conf['index.']['autoAddIndexChars'] == 1)) {
					// get the first char of the term (UTF8)
					// TODO: Make the RegEx configurable to make ZIP-Codes possible
					preg_match('/^./' . $this->conf['modifier'], $termArray['term'], $match);
					$newIndexChar = $match[0];
					$indexArray[$newIndexChar] = NULL;
					$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[index]=' . urlencode($newIndexChar);
					$indexArray[$newIndexChar] = $this->local_cObj->typolink($newIndexChar, $typolinkConf);
					$terms[$termKey]['indexChar'] = $newIndexChar;
				}
			}
		}

		// TODO Sorting of the index (UTF8)
		ksort($indexArray, SORT_LOCALE_STRING);

		return $indexArray;
	}

	function renderPageBrowser(&$markerArray, $resultCount) {
		$this->internal['res_count'] = $resultCount;
		$this->internal['results_at_a_time'] = $this->conf['pagebrowser.']['results_at_a_time'] ? intval($this->conf['pagebrowser.']['results_at_a_time']) : 20;
		$this->internal['maxPages'] = $this->conf['pagebrowser.']['maxPages'] ? intval($this->conf['pagebrowser.']['maxPages']) : 3;
		$this->internal['dontLinkActivePage'] = $this->conf['pagebrowser.']['dontLinkActivePage'] === '0' ? FALSE : TRUE;
		$this->internal['showFirstLast'] = $this->conf['pagebrowser.']['showFirstLast'] === '0' ? FALSE : TRUE;
		$this->internal['pagefloat'] = strlen($this->conf['pagebrowser.']['pagefloat']) > 0 ? $this->conf['pagebrowser.']['pagefloat'] : 'center';
		$this->internal['showRange'] = $this->conf['pagebrowser.']['showRange'];
		$this->pi_alwaysPrev = intval($this->conf['pagebrowser.']['alwaysPrev']);

		if (($this->internal['res_count'] > $this->internal['results_at_a_time']) && ($this->conf['pagebrowser.']['enable'] > 0)) {
			$wrapArray = is_array($this->conf['pagebrowser.']['wraps.']) ? $this->conf['pagebrowser.']['wraps.'] : array();
			$pointerName = strlen($this->conf['pagebrowser.']['pointerName']) > 0 ? $this->conf['pagebrowser.']['pointerName'] : 'pointer';
			$enableHtmlspecialchars = $this->conf['pagebrowser.']['enableHtmlspecialchars'] === '0' ? FALSE : TRUE;
			$markerArray['###PAGEBROWSER###'] = $this->pi_list_browseresults($this->conf['pagebrowser.']['showResultCount'], $this->conf['pagebrowser.']['tableParams'], $wrapArray, $pointerName, $enableHtmlspecialchars);
		} else {
			$markerArray['###PAGEBROWSER###'] = '';
		}
	}

	function renderSearchBox(&$markerArray) {
		if ($this->conf['searchbox.']['enable'] > 0) {
			$markerArray['###SEARCHBOX###'] = $this->pi_list_searchBox();
		} else {
			$markerArray['###SEARCHBOX###'] = '';
		}
	}

	protected function removeUnfilledMarker($content) {
		return preg_replace('/###.*?###/', '', $content);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']);
}
?>