<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contagged');
return array(
	'tx_contagged' => $extensionPath . 'class.tx_contagged.php',
	'tx_contagged_model_mapper' => $extensionPath . 'model/class.tx_contagged_model_mapper.php',
	'tx_contagged_model_terms' => $extensionPath . 'model/class.tx_contagged_model_terms.php',
);
?>