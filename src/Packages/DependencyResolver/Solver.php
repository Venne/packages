<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\DependencyResolver;

use Nette\InvalidArgumentException;
use Nette\Object;
use Venne\Packages\IPackage;
use Venne\Packages\PackageManager;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class Solver extends Object
{

	/** @var \Venne\Packages\IPackage[] */
	private $installedPackages;

	/** @var \Venne\Packages\IPackage[] */
	private $packages;

	/** @var mixed[] */
	private $packagesConfig;

	/** @var string */
	private $libsDir;

	/**
	 * @param \Venne\Packages\IPackage[] $packages
	 * @param \Venne\Packages\IPackage[] $installedPackages
	 * @param mixed[] $packagesConfig
	 * @param string $libsDir
	 */
	public function __construct(array $packages, array $installedPackages, $packagesConfig, $libsDir)
	{
		$this->packages = $packages;
		$this->installedPackages = $installedPackages;
		$this->packagesConfig = &$packagesConfig;
		$this->libsDir = $libsDir;
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 * @param \Venne\Packages\DependencyResolver\Problem $problem
	 */
	public function testInstall(IPackage $package, Problem $problem = null)
	{
		$installedPackages = &$this->installedPackages;
		$packages = &$this->packages;

		foreach ($package->getRequire() as $name) {
			if (!isset($installedPackages[$name])) {
				if ($problem && isset($packages[$name])) {
					$solver = $this->createSolver();
					$solver->testInstall($packages[$name], $problem);

					$job = new Job(Job::ACTION_INSTALL, $packages[$name]);
					if (!$problem->hasSolution($job)) {
						$problem->addSolution($job);
					}
					$installedPackages[$name] = $packages[$name];
					$tr = array(
						$this->libsDir => '%libsDir%',
					);
					$this->packagesConfig[$name] = array(
						PackageManager::PACKAGE_STATUS => PackageManager::STATUS_INSTALLED,
						PackageManager::PACKAGE_PATH => str_replace(array_keys($tr), array_merge($tr), $package->getPath()),
					);

				} else {
					throw new InvalidArgumentException(sprintf(
						'Package \'%s\' depend on \'%s\', which was not found.',
						$package->getName(),
						$name
					));
				}
			}
		}
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 * @param \Venne\Packages\DependencyResolver\Problem $problem
	 */
	public function testUninstall(IPackage $package, Problem $problem = null)
	{
		$installedPackages = &$this->installedPackages;

		foreach ($installedPackages as $sourcePackage) {
			if ($sourcePackage->getName() === $package->getName()) {
				continue;
			}

			foreach ($sourcePackage->getRequire() as $name) {
				if ($name == $package->getName()) {
					if ($problem) {
						$solver = $this->createSolver();
						$solver->testUninstall($sourcePackage, $problem);

						$job = new Job(Job::ACTION_UNINSTALL, $sourcePackage);
						if (!$problem->hasSolution($job)) {
							$problem->addSolution($job);
						}
					} else {
						throw new InvalidArgumentException(sprintf(
							'Package \'%s\' depend on \'%s\'.',
							$sourcePackage->getName(),
							$package->getName()
						));
					}
				}
			}
		}
	}

	/**
	 * @return \Venne\Packages\DependencyResolver\Solver
	 */
	private function createSolver()
	{
		return new static($this->packages, $this->installedPackages, $this->packagesConfig, $this->libsDir);
	}

}
