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

use Kdyby\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\Utils\Neon;

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
			'packagesDir' => '%appDir%/../.venne.packages'
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
			if (!isset($container->parameters[$name])) {
				$container->parameters[$name] = $container->expand($path);
			}
		}

		if (!file_exists($container->parameters['packagesDir'] . '/config.neon')) {
			file_put_contents($container->parameters['packagesDir'] . '/config.neon', Neon::encode(array(
				'sources' => array('https://raw.github.com/venne/packages-metadata/master/metadata.json'),
			), Neon::BLOCK));
		}

		$packagesConfig = Neon::decode(file_get_contents($container->parameters['packagesDir'] . '/config.neon'));
		$container->addDependency($container->parameters['packagesDir'] . '/config.neon');

		// load packages
		$container->parameters['packages'] = array();
		$packagesDir = $container->expand($config['paths']['packagesDir']);
		if (file_exists($packagesDir . '/packages.php')) {
			$packages = require $packagesDir . '/packages.php';
			foreach ($packages as $name => $items) {
				$container->parameters['packages'][$name] = $items;
				$container->parameters['packages'][$name]['path'] = $container->expand($items['path']);
			}
		}

		// packages
		$container->addDefinition($this->prefix('packageManager'))
			->setClass('Venne\Packages\PackageManager', array('@container', $container->parameters['configDir'], $container->parameters['libsDir'], $container->parameters['resourcesDir'], $container->parameters['packagesDir'], $container->expand($config['packageManager']['packageFiles']), $packagesConfig['sources']));

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
				->addTag(ConsoleExtension::COMMAND_TAG);
		}

		// macros
		$container->getDefinition('nette.latte')
			->addSetup('$s = Venne\Packages\Latte\Macros\UIMacros::install(?->getCompiler()); $s->injectPathResolver(?)', array('@self', '@Venne\Packages\PathResolver'));
	}

}

