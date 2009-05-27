<?php
/**
 * This is an example configuration for RealURL to work together with contagged.
 * You have to add only the PostVarSet (lines 53 to 72).
 * This setup is based on EXT:aeUrlTool default realurl configuration.
 */
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'] = array ( 
    '_DEFAULT' => array (
        'init' => array (
	      'enableCHashCache' => TRUE,
	      'appendMissingSlash' => 'ifNotFile,redirect',
	      'adminJumpToBackend' => TRUE,
	      'enableUrlDecodeCache' => TRUE,
	      'enableUrlEncodeCache' => TRUE,
	      'emptyUrlReturnValue' => '/',
        ),
        'redirects' => array (
        ),
        'preVars' => array (
            0 => array (
                'GETvar' => 'no_cache',
                'valueMap' => array (
                    'nc' => '1',
                ),
                'noMatch' => 'bypass'
            ),
            1 => array (
                'GETvar' => 'L',
                'valueMap' => array (
                    'de' => '0',
                    'en' => '1',
                ),
                'noMatch' => 'bypass',
            ),
            2 => array (
                'GETvar' => 'lang',
                'valueMap' => array (
                    'de' => 'de',
                    'en' => 'en',
                ),
                'noMatch' => 'bypass',
            ),
        ),
        'pagePath' => array (
            'type' => 'user',
            'userFunc' => 'EXT:realurl/class.tx_realurl_advanced.php:&tx_realurl_advanced->main',
            'spaceCharacter' => '-',
            'languageGetVar' => 'L',
            'expireDays' => '7',
        ),
        'fixedPostVars' => array (
        ),
        'postVarSets' => array (
            '_DEFAULT' => array (
               'char' => array (
                    array (
                        'GETvar' => 'tx_contagged[index]',
                    ),                           
                ),                               
               'source' => array (
                    array (
	                   'GETvar' => 'tx_contagged[termSource]',              
                    ),                           
                ),
				'term' => array (
                    array (                      
                       'GETvar' => 'tx_contagged[termUid]',
	                   'lookUpTable' => array (
	                       'table' => 'tx_contagged_terms',
	                       'id_field' => 'uid',
	                       'alias_field' => 'term_main',
	                       'addWhereClause' => ' AND NOT deleted',
	                       'useUniqueCache' => '1',
	                       'useUniqueCache_conf' => array (
	                           'strtolower' => '1',
	                           'spaceCharacter' => '-',
	                       ),
                   		),
                    ),                           
                ),                             
               'searchword' => array (           
                    array (                      
                        'GETvar' => 'tx_contagged[sword]',
                    ),                           
                ),                               
               'backTo' => array (            
                    array (                      
                        'GETvar' => 'tx_contagged[backPid]',
                    ),
                ),
            ),
        ),
        'fileName' => array (
            'defaultToHTMLsuffixOnPrev' => FALSE,
            'index' => array (
                'rss.xml' => array (
                    'keyValues' => array (
                        'type' => '100',
                    ),
                ),
                'rss091.xml' => array (
                    'keyValues' => array (
                        'type' => '101',
                    ),
                ),
                'rdf.xml' => array (
                    'keyValues' => array (
                        'type' => '102',
                    ),
                ),
                'atom.xml' => array (
                    'keyValues' => array (
                        'type' => '103',
                    ),
                ),
            ),
        ),
    ),

); 
?>