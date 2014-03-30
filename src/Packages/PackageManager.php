<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages;

use Exception;
use Nette\DI\Config\Adapters\PhpAdapter;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\Object;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Venne\Packages\DependencyResolver\Job;
use Venne\Packages\DependencyResolver\Problem;
use Venne\Packages\DependencyResolver\Solver;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class PackageManager extends Object
{

	const PACKAGE_PATH = 'path';

	const PACKAGE_STATUS = 'status';

	const PACKAGE_METADATA = 'metadata';

	const PACKAGE_VERSION = 'version';

	const STATUS_UNINSTALLED = 'uninstalled';

	const STATUS_INSTALLED = 'installed';

	const ACTION_INSTALL = 'install';

	const ACTION_UNINSTALL = 'uninstall';

	const ACTION_REGISTER = 'register';

	const ACTION_UNREGISTER = 'unregister';


	/** @var array */
	public $onInstall;

	/** @var array */
	public $onUninstall;

	/** @var array */
	public $onUpgrade;

	/** @var array */
	public $onRegister;

	/** @var array */
	public $onUnregister;

	/** @var array */
	private static $statuses = array(
		self::STATUS_INSTALLED => 'Installed',
		self::STATUS_UNINSTALLED => 'Uninstalled',
	);

	/** @var array */
	private $packageFiles = array();

	/** @var Container|\SystemContainer */
	private $context;

	/** @var string */
	private $configDir;

	/** @var string */
	private $libsDir;

	/** @var string */
	private $resourcesDir;

	/** @var string */
	private $packagesDir;

	/** @var array */
	private $metadataSources = array();

	/** @var array */
	private $_packageConfig;

	/** @var IPackage[] */
	private $_packages;

	/** @var array */
	private $_globalMetadata;

	/** @var array */
	private $_lockFileData;


	/**
	 * @param Container $context
	 * @param $configDir
	 * @param $libsDir
	 * @param $resourcesDir
	 * @param $packagesDir
	 * @param array $packageFiles
	 * @param array $metadataSources
	 */
	public function __construct(Container $context, $configDir, $libsDir, $resourcesDir, $packagesDir, array $packageFiles, array $metadataSources = array())
	{
		$this->context = $context;
		$this->configDir = $configDir;
		$this->libsDir = $libsDir;
		$this->resourcesDir = $resourcesDir;
		$this->packagesDir = $packagesDir;
		$this->packageFiles = $packageFiles;
		$this->metadataSources = $metadataSources;
	}


	/**
	 * @return string
	 */
	public function getLibsDir()
	{
		return $this->libsDir;
	}


	/**
	 * @return string
	 */
	public function getConfigDir()
	{
		return $this->configDir;
	}


	/**
	 * @return string
	 */
	public function getResourcesDir()
	{
		return $this->resourcesDir;
	}


	/**
	 * Reload info.
	 */
	private function reloadInfo()
	{
		$this->_packages = NULL;
	}


	/**
	 * Create instance of package.
	 *
	 * @param $name
	 * @return IPackage
	 * @throws \Nette\InvalidArgumentException
	 */
	public function createInstance($name)
	{
		$lockFileData = $this->getLockFileData();
		$packageConfig = $this->getPackageConfig();

		if (isset($lockFileData[$name])) {
			$data = $lockFileData[$name];
		} elseif (isset($packageConfig[$name])) {
			$data = $packageConfig[$name][self::PACKAGE_METADATA];
			$data['name'] = $name;
		} else {
			throw new InvalidArgumentException("Package '{$name}' does not exist.");
		}

		$path = $this->libsDir . '/' . $name;
		foreach ($this->packageFiles as $packageFile) {
			if (file_exists($path . '/' . $packageFile)) {
				$class = $this->getPackageClassByFile($path . '/' . $packageFile);
				include_once $path . '/' . $packageFile;
				return new $class;
			}
		}

		$package = new VirtualPackage($data, $path);
		if (($metadata = $this->getGlobalMetadata($package)) !== NULL) {
			$data = Arrays::mergeTree($data, array('extra' => array('venne' => $metadata)));
			$package = new VirtualPackage($data, $path);
		}

		return $package;
	}


	/**
	 * Get package status
	 *
	 * @param IPackage $package
	 * @return string
	 * @throws \Nette\InvalidArgumentException
	 */
	public function getStatus(IPackage $package)
	{
		$packageConfig = $this->getPackageConfig();
		return $packageConfig[$package->getName()][self::PACKAGE_STATUS];
	}


	/**
	 * @param IPackage $package
	 * @return string
	 * @throws \Nette\InvalidArgumentException
	 */
	public function getVersion(IPackage $package)
	{
		$data = $this->getLockFileData();
		$packageConfig = $this->getPackageConfig();

		if (!isset($data[$package->getName()])) {
			if (!isset($packageConfig[$package->getName()])) {
				throw new InvalidArgumentException("Package '{$package->getName()}' does not exist.");
			}

			return $packageConfig[$package->getName()][self::PACKAGE_VERSION];
		}

		return $data[$package->getName()]['version'];
	}


	/**
	 * @return array
	 */
	public function registerAvailable()
	{
		$actions = array();

		$allPackages = array_keys($this->getLockFileData());
		$registeredPackages = array_keys($this->getPackageConfig());
		foreach (array_diff($allPackages, $registeredPackages) as $package) {
			$this->register($this->createInstance($package));
			$actions[] = array($package => self::ACTION_REGISTER);
		}

		return $actions;
	}


	/**
	 * @return array
	 */
	public function installAvailable()
	{
		$actions = array();

		while (TRUE) {
			$packages = $this->getPackagesByStatus(self::STATUS_UNINSTALLED);
			if (!count($packages)) {
				break;
			}

			$package = reset($packages);
			foreach ($this->testInstall($package)->getSolutions() as $job) {
				if ($job->getAction() === Job::ACTION_INSTALL) {
					$this->install($job->getPackage());
					$actions[] = array($job->getPackage()->getName() => self::ACTION_INSTALL);

				} else if ($job->getAction() === Job::ACTION_UNINSTALL) {
					$this->uninstall($job->getPackage());
					$actions[] = array($job->getPackage()->getName() => self::ACTION_UNINSTALL);

				}
			}
			$this->install($package);
			$actions[] = array($package->getName() => self::ACTION_INSTALL);
		}

		return $actions;
	}


	/**
	 * @return array
	 */
	public function uninstallAbsent()
	{
		$actions = array();

		$allPackages = array_keys($this->getLockFileData());
		$registeredPackages = array_keys($this->getPackageConfig());
		foreach (array_diff($registeredPackages, $allPackages) as $name) {
			$package = $this->createInstance($name);
			if ($this->getStatus($package) === self::STATUS_INSTALLED) {
				$this->uninstall($package);
				$actions[] = array($name => self::ACTION_UNINSTALL);
			}
			$this->unregister($package);
			$actions[] = array($package->getName() => self::ACTION_UNREGISTER);
		}

		return $actions;
	}


	/**
	 * Installation of package.
	 *
	 * @param IPackage $package
	 * @throws InvalidArgumentException|InvalidStateException
	 */
	public function install(IPackage $package)
	{
		if ($this->getStatus($package) === self::STATUS_INSTALLED) {
			throw new InvalidArgumentException("Package '{$package->getName()}' is already installed");
		}

		$dependencyResolver = $this->createSolver();
		$dependencyResolver->testInstall($package);

		foreach ($package->getInstallers() as $class) {
			try {
				$installer = $this->context->createInstance($class);
				$installer->install($package);
			} catch (Exception $e) {
				foreach ($package->getInstallers() as $class2) {
					if ($class === $class2) {
						break;
					}

					$installer = $this->context->createInstance($class2);
					$installer->uninstall($package);
				}

				throw new InvalidStateException($e->getMessage());
			}
		}

		$this->setStatus($package, self::STATUS_INSTALLED);
		$this->reloadInfo();
		$this->onInstall($this, $package);
	}


	/**
	 * Uninstallation of package.
	 *
	 * @param IPackage $package
	 * @throws InvalidArgumentException|InvalidStateException
	 */
	public function uninstall(IPackage $package)
	{
		if ($this->getStatus($package) === self::STATUS_UNINSTALLED) {
			throw new InvalidArgumentException("Package '{$package->getName()}' is already uninstalled");
		}

		$dependencyResolver = $this->createSolver();
		$dependencyResolver->testUninstall($package);

		foreach ($package->getInstallers() as $class) {
			try {
				$installer = $this->context->createInstance($class);
				$installer->uninstall($package);
			} catch (Exception $e) {
				foreach ($package->getInstallers() as $class2) {
					if ($class === $class2) {
						break;
					}

					$installer = $this->context->createInstance($class2);
					$installer->install($package);
				}

				throw new InvalidStateException($e->getMessage());
			}
		}

		$this->setStatus($package, self::STATUS_UNINSTALLED);
		$this->reloadInfo();
		$this->onUninstall($this, $package);
	}


	/**
	 * @param IPackage $package
	 * @return Problem
	 */
	public function testInstall(IPackage $package)
	{
		$problem = new Problem;
		$dependencyResolver = $this->createSolver();
		$dependencyResolver->testInstall($package, $problem);
		return $problem;
	}


	/**
	 * @param IPackage $package
	 * @return Problem
	 */
	public function testUninstall(IPackage $package)
	{
		$problem = new Problem;
		$dependencyResolver = $this->createSolver();
		$dependencyResolver->testUninstall($package, $problem);
		return $problem;
	}


	/**
	 * Get activated packages.
	 *
	 * @return IPackage[]
	 */
	public function getPackages()
	{
		if ($this->_packages === NULL) {
			$this->_packages = array();
			foreach ($this->getPackageConfig() as $name => $values) {
				$this->_packages[$name] = $this->createInstance($name);
			}
		}

		return $this->_packages;
	}


	/**
	 * @return string
	 */
	private function getPackageConfigPath()
	{
		return $this->packagesDir . '/packages.php';
	}


	/**
	 * @return array
	 */
	private function & getPackageConfig()
	{
		if ($this->_packageConfig === NULL) {
			$config = new PhpAdapter;

			if (!file_exists($this->getPackageConfigPath())) {
				@mkdir(dirname($this->getPackageConfigPath()));
				file_put_contents($this->getPackageConfigPath(), $config->dump(array()));
			}

			$this->_packageConfig = $config->load($this->getPackageConfigPath());
		}

		return $this->_packageConfig;
	}


	private function savePackageConfig()
	{
		$config = new PhpAdapter;
		file_put_contents($this->getPackageConfigPath(), $config->dump($this->_packageConfig));
	}


	/**
	 * @param $file
	 * @return string
	 * @throws InvalidArgumentException
	 */
	private function getPackageClassByFile($file)
	{
		$classes = Helpers::getClassesFromFile($file);

		if (count($classes) !== 1) {
			throw new InvalidArgumentException("File '{$file}' must contain only one class.");
		}

		return $classes[0];
	}


	/**
	 * Set package status
	 *
	 * @param IPackage $package
	 * @param $status
	 * @throws InvalidArgumentException
	 */
	private function setStatus(IPackage $package, $status)
	{
		if (!isset(self::$statuses[$status])) {
			throw new InvalidArgumentException("Status '{$status}' not exists.");
		}

		$packageConfig = & $this->getPackageConfig();
		$packageConfig[$package->getName()][self::PACKAGE_STATUS] = $status;
		$this->savePackageConfig();
	}


	/**
	 * @return Solver
	 */
	private function createSolver()
	{
		return new Solver($this->getPackages(), $this->getPackagesByStatus(self::STATUS_INSTALLED), $this->getPackageConfig(), $this->libsDir);
	}


	/**
	 * Get packages by status.
	 *
	 * @param $status
	 * @return IPackage[]
	 * @throws InvalidArgumentException
	 */
	private function getPackagesByStatus($status)
	{
		if (!isset(self::$statuses[$status])) {
			throw new InvalidArgumentException("Status '{$status}' not exists.");
		}

		$ret = array();
		foreach ($this->getPackages() as $name => $package) {
			if ($this->getStatus($package) === $status) {
				$ret[$name] = $package;
			}
		}
		return $ret;
	}


	/**
	 * @param $path
	 * @return string
	 */
	private function getFormattedPath($path)
	{
		$path = str_replace('\\', '/', $path);
		$libsDir = str_replace('\\', '/', $this->libsDir);

		$tr = array(
			$libsDir => '%libsDir%',
		);

		return str_replace(array_keys($tr), array_merge($tr), $path);
	}


	/**
	 * @param IPackage $package
	 * @return array
	 * @throws InvalidStateException
	 */
	private function getGlobalMetadata(IPackage $package)
	{
		if ($this->_globalMetadata === NULL) {
			$this->_globalMetadata = array();

			foreach ($this->metadataSources as $source) {
				if (substr($source, 0, 7) == 'http://' || substr($source, 0, 8) == 'https://') {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_URL, $source);
					$data = curl_exec($ch);
				} else {
					$data = file_get_contents($source);
				}

				if (!$data) {
					throw new InvalidStateException("Source '$source' is empty.");
				}

				if ($data) {
					$this->_globalMetadata = Arrays::mergeTree($this->_globalMetadata, Json::decode($data, Json::FORCE_ARRAY));
				}
			}

		}

		if (!isset($this->_globalMetadata[$package->getName()])) {
			return NULL;
		}

		$version = $this->getVersion($package);
		foreach ($this->_globalMetadata[$package->getName()] as $data) {
			if (in_array($version, $data['versions'])) {
				return $data['metadata'];
			}
		}
	}


	/**
	 * @return array
	 */
	private function getLockFileData()
	{
		if ($this->_lockFileData === NULL) {
			$this->_lockFileData = array();
			$data = Json::decode(file_get_contents(dirname($this->libsDir) . '/composer.lock'), Json::FORCE_ARRAY);

			foreach ($data['packages'] as $package) {
				$this->_lockFileData[$package['name']] = $package;
			}
		}

		return $this->_lockFileData;
	}


	/**
	 * Registration of package.
	 *
	 * @param IPackage $package
	 */
	private function register(IPackage $package)
	{
		$packageConfig = & $this->getPackageConfig();
		if (!array_search($package->getName(), $packageConfig)) {
			$packageConfig[$package->getName()] = array(
				self::PACKAGE_STATUS => self::STATUS_UNINSTALLED,
				self::PACKAGE_PATH => $this->getFormattedPath($package->getPath()),
				self::PACKAGE_VERSION => $this->getVersion($package),
				self::PACKAGE_METADATA => array(
					'authors' => $package->getAuthors(),
					'description' => $package->getDescription(),
					'keywords' => $package->getKeywords(),
					'license' => $package->getLicense(),
					'require' => $package->getRequire(),
					'extra' => array(
						'venne' => array(
							'configuration' => $package->getConfiguration(),
							'installers' => $package->getInstallers(),
							'relativePublicPath' => $package->getRelativePublicPath(),
						),
					),
				),
			);
		}
		$this->savePackageConfig();
		$this->onRegister($this, $package);
	}


	/**
	 * Unregistration of package.
	 *
	 * @param IPackage $package
	 */
	private function unregister(IPackage $package)
	{
		$packageConfig = & $this->getPackageConfig();
		unset($packageConfig[$package->getName()]);
		$this->savePackageConfig();
		$this->onUnregister($this, $package);

	}

}

