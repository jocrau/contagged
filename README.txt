This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\'). Needs PHP >5.1.0.

== UPGRADE INSTRUCTIONS ==

1.5.0 -> 1.6.0
- The keys of the GET parameters have changed from 'termUid' to 'uid' and from 'termSource' to 'source'. Adapt your RealURL and CoolURI configuration.
- The TS name of the datasource is taken as GET parameter value for 'source' (was the table name). Instead of 'tx_contagged[source]=tx-contagged_terms' there is now 'tx_contagged[source]=default'.
- The registered key and internal referrer (register:contagged_key) is now 'default_123' instead of 'tx_contagged_term_123'.
- You have to explicitly invoke the parser if contagged should parse also its list content. There is a new TS option 'fieldsToParse' for that.
- The default CSS style for abbr,def,acronym was removed. The essential CSS is now loaded by default. Please check your styles in the frontend.
- The option option 'secureFields' was removed. You have to apply this in the mapping configuration: e.g. "desc_short.htmlSpecialChars = 1".
