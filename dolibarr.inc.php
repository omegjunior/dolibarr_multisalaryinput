<?php

require_once (function () {
	$currentDir = realpath(__DIR__);

	if (! defined('MULTISALARY_DOCUMENT_ROOT')) {
		define('MULTISALARY_DOCUMENT_ROOT', $currentDir);
	}


	$sapi_type = php_sapi_name();

	// Test if batch mode
	if (substr($sapi_type, 0, 3) == 'cgi' || $sapi_type == 'cli') {
		$file = 'master.inc.php';
	} else {
		$file = 'main.inc.php';
	}


	do {
		if (is_file($currentDir . '/' . $file)) {
			break;
		}

		$nextDir = dirname($currentDir);

		if ($nextDir === $currentDir) {
			trigger_error('Dolibarr core not found', E_USER_ERROR);
			exit;
		}

		$currentDir = $nextDir;
	} while (true);


	$dolibarrRoot = $currentDir;

	return $dolibarrRoot . '/'. $file;
})();