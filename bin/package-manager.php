<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

$rootDir = getcwd();
$libsDir = $rootDir . '/vendor';
$wwwDir = $rootDir . '/www';
$appDir = $rootDir . '/app';
$packagesDir = $rootDir. '/.venne.packages';

if (!file_exists($packagesDir)) {
	if (is_writable(dirname($packagesDir))) {
		mkdir($packagesDir);
	} else {
		die("Path '$packagesDir' does not exists.");
	}
}

if (!is_writable($packagesDir)) {
	die("Path '$packagesDir' it not writable.");
}

foreach(array('log', 'temp') as $dir) {
	if (!file_exists($packagesDir . '/' . $dir)) {
		mkdir($packagesDir . '/' . $dir);
	}
}

if (!file_exists($libsDir . '/autoload.php')) {
	die('autoload.php file can not be found.');
}

require_once $libsDir . '/autoload.php';

$configurator = new Nette\Configurator;
$configurator->addParameters(array(
	'appDir' => $appDir,
	'wwwDir' => $wwwDir,
));

//$configurator->setDebugMode(TRUE);  // debug mode MUST NOT be enabled on production server
$configurator->enableDebugger($packagesDir . '/log');

$configurator->setTempDirectory($packagesDir . '/temp');
$configurator->addConfig(dirname(__DIR__) . '/config/config.neon');

$container = $configurator->createContainer();


$container->getService('application')->run();
