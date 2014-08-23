<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\Installers;

use Nette\Neon\Neon;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Validators;
use Venne\Packages\Helpers;
use Venne\Packages\IInstaller;
use Venne\Packages\IPackage;
use Venne\Packages\PackageManager;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class StructureInstaller extends Object implements IInstaller
{

	/** @var string[] */
	private $actions = array();

	/** @var string */
	private $resourcesDir;

	/** @var string */
	private $configDir;

	/**
	 * @param \Venne\Packages\PackageManager $packageManager
	 */
	public function __construct(PackageManager $packageManager)
	{
		$this->resourcesDir = $packageManager->getResourcesDir();
		$this->configDir = $packageManager->getConfigDir();
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 */
	public function install(IPackage $package)
	{
		try {
			$name = $package->getName();
			$configuration = $package->getConfiguration();

			// create resources dir
			if ($package->getRelativePublicPath()) {
				$resourcesDir = $this->resourcesDir;
				$packageDir = $resourcesDir . '/' . $name;
				$targetDir = realpath($package->getPath() . $package->getRelativePublicPath());
				if (!is_dir($packageDir) && is_dir($targetDir)) {
					umask(0000);
					@mkdir(dirname($packageDir), 0777, true);
					if (!@symlink(Helpers::getRelativePath(dirname($packageDir), $targetDir), $packageDir) && !is_dir($packageDir)) {
						FileSystem::copy($targetDir, $packageDir);
					}

					$this->actions[] = function () use ($resourcesDir) {
						if (is_link($resourcesDir)) {
							unlink($resourcesDir);
						} else {
							FileSystem::delete($resourcesDir);
						}
					};
				}
			}

			// update main config.neon
			if (count($configuration) > 0) {
				$orig = $data = $this->loadConfig();
				$data = array_merge_recursive($data, $configuration);
				$this->saveConfig($data);

				$this->actions[] = function ($self) use ($orig) {
					$self->saveConfig($orig);
				};
			}
		} catch (\Exception $e) {
			$actions = array_reverse($this->actions);

			try {
				foreach ($actions as $action) {
					$action($this);
				}
			} catch (\Exception $ex) {
				echo $ex->getMessage();
			}

			throw $e;
		}
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 */
	public function uninstall(IPackage $package)
	{
		$name = $package->getName();
		$configuration = $package->getConfiguration();

		// update main config.neon
		if (count($configuration) > 0) {
			$orig = $data = $this->loadConfig();
			$data = $this->getRecursiveDiff($data, $configuration);

			// remove extension parameters
			$configuration = $package->getConfiguration();
			if (isset($configuration['extensions'])) {
				foreach ($configuration['extensions'] as $key => $values) {
					if (isset($data[$key])) {
						unset($data[$key]);
					}
				}
			}

			$this->saveConfig($data);

			$this->actions[] = function ($self) use ($orig) {
				$self->saveConfig($orig);
			};
		}

		// remove resources dir
		$resourcesDir = $this->resourcesDir . '/' . $name;
		if (is_dir($resourcesDir)) {
			if (is_link($resourcesDir)) {
				unlink($resourcesDir);
			} else {
				FileSystem::delete($resourcesDir);
			}
		}
	}

	/**
	 * @param mixed[] $arr1
	 * @param mixed[] $arr2
	 * @return mixed[]
	 */
	private function getRecursiveDiff($arr1, $arr2)
	{
		$isList = Validators::isList($arr1);
		$arr2IsList = Validators::isList($arr2);

		foreach ($arr1 as $key => $item) {
			if (!is_array($arr1[$key])) {

				// if key is numeric, remove the same value
				if (is_numeric($key) && ($pos = array_search($arr1[$key], $arr2)) !== false) {
					unset($arr1[$key]);
				} //

				// else remove the same key
				else if ((!$isList && isset($arr2[$key])) || ($isList && $arr2IsList && array_search($item, $arr2) !== false)) {
					unset($arr1[$key]);
				} //

			} elseif (isset($arr2[$key])) {
				$arr1[$key] = $item = $this->getRecursiveDiff($arr1[$key], $arr2[$key]);

				if (is_array($item) && count($item) === 0) {
					unset($arr1[$key]);
				}
			}
		}

		if ($isList) {
			$arr1 = array_merge($arr1);
		}

		return $arr1;
	}

	/**
	 * @return string
	 */
	private function getConfigPath()
	{
		return $this->configDir . '/config.neon';
	}

	/**
	 * @return mixed[]
	 */
	private function loadConfig()
	{
		return Neon::decode(file_get_contents($this->getConfigPath()));
	}

	/**
	 * @param mixed[] $data
	 */
	private function saveConfig($data)
	{
		file_put_contents($this->getConfigPath(), Neon::encode($data, Neon::BLOCK));
	}

}

