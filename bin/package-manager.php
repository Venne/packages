<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

$paths = array(
	dirname(__DIR__) . '/vendor' => dirname(dirname(__DIR__)),
	dirname(dirname(dirname(__DIR__))) => dirname(dirname(dirname(dirname(__DIR__)))),
);

foreach ($paths as $vendor => $managerDir) {
	if (file_exists($vendor . '/autoload.php')) {
		require_once $vendor . '/autoload.php';
		$managerDir = $managerDir . '/.venne.packages';
		$loaded = TRUE;
		break;
	}
}

if (!isset($loaded)) {
	die('autoload.php file can not be found.');
}

if (!file_exists($managerDir)) {
	if (is_writable(dirname($managerDir))) {
		mkdir($managerDir);
	} else {
		die("Path '$managerDir' does not exists.");
	}
}

if (!is_writable($managerDir)) {
	die("Path '$managerDir' it not writable.");
}

foreach(array('log', 'temp') as $dir) {
	if (!file_exists($managerDir . '/' . $dir)) {
		mkdir($managerDir . '/' . $dir);
	}
}

$configurator = new Nette\Configurator;
$configurator->addParameters(array(
	'appDir' => dirname($managerDir) . '/app',
	'wwwDir' => dirname($managerDir) . '/www',
));

//$configurator->setDebugMode(TRUE);  // debug mode MUST NOT be enabled on production server
$configurator->enableDebugger($managerDir . '/log');

$configurator->setTempDirectory($managerDir . '/temp');
$configurator->addConfig(dirname(__DIR__) . '/config/config.neon');

$container = $configurator->createContainer();


$container->getService('application')->run();
