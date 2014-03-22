<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\DI;

use Nette\DI\CompilerExtension;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class PackagesExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = array(
		'paths' => array(
			'configDir' => '%appDir%/config',
			'resourcesDir' => '%wwwDir%/resources',
			'libsDir' => '%appDir%/../vendor',
			'managerDir' => '%appDir%/../.venne.packages'
		),
		'packageManager' => array(
			'packageFiles' => array(
				'.Venne.php',
				'.venne.php',
			),
		),
	);


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		foreach ($config['paths'] as $name => $path) {
			$container->parameters[$name] = $container->expand($path);
		}

		// load packages
		$container->parameters['packages'] = array();
		$managerDir = $container->expand($config['paths']['managerDir']);
		if (file_exists($managerDir . '/packages.php')) {
			$packages = require $managerDir . '/packages.php';
			foreach ($packages as $name => $items) {
				$container->parameters['packages'][$name] = $items;
				$container->parameters['packages'][$name]['path'] = $container->expand($items['path']);
			}
		}

		// packages
		$container->addDefinition($this->prefix('packageManager'))
			->setClass('Venne\Packages\PackageManager', array('@container', $container->parameters['configDir'], $container->parameters['libsDir'], $container->parameters['resourcesDir'], $container->parameters['managerDir'], $container->expand($config['packageManager']['packageFiles'])));

		// helpers
		$container->addDefinition($this->prefix('pathResolver'))
			->setClass('Venne\Packages\PathResolver', array($container->expand('%packages%')));

		// Commands
		$commands = array(
			'packageSync' => 'Venne\Packages\Commands\Sync',
			'packageInstall' => 'Venne\Packages\Commands\Install',
			'packageUninstall' => 'Venne\Packages\Commands\Uninstall',
			'packageList' => 'Venne\Packages\Commands\List',
		);
		foreach ($commands as $name => $cmd) {
			$container->addDefinition($this->prefix(lcfirst($name) . 'Command'))
				->setClass("{$cmd}Command")
				->addTag('kdyby.console.command');
		}

		// macros
		$container->getDefinition('nette.latte')
			->addSetup('$s = Venne\Packages\Latte\Macros\UIMacros::install(?->compiler); $s->injectPathResolver(?)', array('@self', '@Venne\Packages\PathResolver'));
	}

}

