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
use Venne\Packages\LinkConstraint\VersionConstraint;
use Venne\Packages\Version\VersionParser;

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

	/** @var callable[] */
	public $onInstall;

	/** @var callable[] */
	public $onUninstall;

	/** @var callable[] */
	public $onUpgrade;

	/** @var callable[] */
	public $onRegister;

	/** @var callable[] */
	public $onUnregister;

	/** @var string[] */
	private static $statuses = array(
		self::STATUS_INSTALLED => 'Installed',
		self::STATUS_UNINSTALLED => 'Uninstalled',
	);

	/** @var string[] */
	private $packageFiles = array();

	/** @var \Nette\DI\Container|\SystemContainer */
	private $context;

	/** @var string */
	private $configDir;

	/** @var string */
	private $libsDir;

	/** @var string */
	private $resourcesDir;

	/** @var string */
	private $packagesDir;

	/** @var string[] */
	private $metadataSources = array();

	/** @var mixed[] */
	private $_packageConfig;

	/** @var \Venne\Packages\IPackage[] */
	private $_packages;

	/** @var array */
	private $_globalMetadata;

	/** @var array */
	private $_lockFileData;

	/** @var \Venne\Packages\Version\VersionParser */
	private $versionParser;

	/**
	 * @param \Nette\DI\Container $context
	 * @param string $configDir
	 * @param string $libsDir
	 * @param string $resourcesDir
	 * @param string $packagesDir
	 * @param string[] $packageFiles
	 * @param string[] $metadataSources
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
		$this->versionParser = new VersionParser;
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
		$this->_packages = null;
	}

	/**
	 * Create instance of package.
	 *
	 * @param string $name
	 * @return \Venne\Packages\IPackage
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
			throw new InvalidArgumentException(sprintf('Package \'%s\' does not exist.', $name));
		}

		$path = $this->libsDir . '/' . $name;
		foreach ($this->packageFiles as $packageFile) {
			if (is_file($path . '/' . $packageFile)) {
				$class = $this->getPackageClassByFile($path . '/' . $packageFile);
				include_once $path . '/' . $packageFile;

				return new $class;
			}
		}

		$package = new VirtualPackage($data, $path);
		if (($metadata = $this->getGlobalMetadata($package)) !== null) {
			$data = Arrays::mergeTree($data, array(
				'extra' => array(
					'venne' => $metadata
				)
			));
			$package = new VirtualPackage($data, $path);
		}

		return $package;
	}

	/**
	 * Get package status
	 *
	 * @param \Venne\Packages\IPackage $package
	 * @return string
	 */
	public function getStatus(IPackage $package)
	{
		$packageConfig = $this->getPackageConfig();

		return $packageConfig[$package->getName()][self::PACKAGE_STATUS];
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 * @return string
	 */
	public function getVersion(IPackage $package)
	{
		$data = $this->getLockFileData();
		$packageConfig = $this->getPackageConfig();

		if (!isset($data[$package->getName()])) {
			if (!isset($packageConfig[$package->getName()])) {
				throw new InvalidArgumentException(sprintf('Package \'%s\' does not exist.', $package->getName()));
			}

			return $packageConfig[$package->getName()][self::PACKAGE_VERSION];
		}

		return $data[$package->getName()]['version'];
	}

	/**
	 * @return string[]
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
	 * @return string[]
	 */
	public function installAvailable()
	{
		$actions = array();

		while (true) {
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
	 * @return string[]
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
	 * @param \Venne\Packages\IPackage $package
	 */
	public function install(IPackage $package)
	{
		if ($this->getStatus($package) === self::STATUS_INSTALLED) {
			throw new InvalidArgumentException(sprintf('Package \'%s\' is already installed', $package->getName()));
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
	 * @param \Venne\Packages\IPackage $package
	 */
	public function uninstall(IPackage $package)
	{
		if ($this->getStatus($package) === self::STATUS_UNINSTALLED) {
			throw new InvalidArgumentException(sprintf('Package \'%s\' is already uninstalled', $package->getName()));
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
	 * @param \Venne\Packages\IPackage $package
	 * @return \Venne\Packages\DependencyResolver\Problem
	 */
	public function testInstall(IPackage $package)
	{
		$problem = new Problem;
		$dependencyResolver = $this->createSolver();
		$dependencyResolver->testInstall($package, $problem);

		return $problem;
	}

	/**
	 * @param \Venne\Packages\IPackage $package
	 * @return \Venne\Packages\DependencyResolver\Problem
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
	 * @return \Venne\Packages\IPackage[]
	 */
	public function getPackages()
	{
		if ($this->_packages === null) {
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
	 * @return mixed
	 */
	private function & getPackageConfig()
	{
		if ($this->_packageConfig === null) {
			$config = new PhpAdapter;

			if (!is_file($this->getPackageConfigPath())) {
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
	 * @param string $file
	 * @return string
	 */
	private function getPackageClassByFile($file)
	{
		$classes = Helpers::getClassesFromFile($file);

		if (count($classes) !== 1) {
			throw new InvalidArgumentException(sprintf('File \'%s\' must contain only one class.', $file));
		}

		return $classes[0];
	}

	/**
	 * Set package status
	 *
	 * @param \Venne\Packages\IPackage $package
	 * @param string $status
	 */
	private function setStatus(IPackage $package, $status)
	{
		if (!isset(self::$statuses[$status])) {
			throw new InvalidArgumentException(sprintf('Status \'%s\' not exists.', $status));
		}

		$packageConfig = &$this->getPackageConfig();
		$packageConfig[$package->getName()][self::PACKAGE_STATUS] = $status;
		$this->savePackageConfig();
	}

	/**
	 * @return \Venne\Packages\DependencyResolver\Solver
	 */
	private function createSolver()
	{
		return new Solver($this->getPackages(), $this->getPackagesByStatus(self::STATUS_INSTALLED), $this->getPackageConfig(), $this->libsDir);
	}

	/**
	 * Get packages by status.
	 *
	 * @param string $status
	 * @return \Venne\Packages\IPackage[]
	 */
	private function getPackagesByStatus($status)
	{
		if (!isset(self::$statuses[$status])) {
			throw new InvalidArgumentException(sprintf('Status \'%s\' not exists.', $status));
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
	 * @param string $path
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
	 * @param \Venne\Packages\IPackage $package
	 * @return mixed
	 */
	private function getGlobalMetadata(IPackage $package)
	{
		if ($this->_globalMetadata === null) {
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
					throw new InvalidStateException(sprintf('Source \'$source\' is empty.', $source));
				}

				if ($data) {
					$this->_globalMetadata = Arrays::mergeTree($this->_globalMetadata, Json::decode($data, Json::FORCE_ARRAY));
				}
			}

		}

		if (!isset($this->_globalMetadata[$package->getName()])) {
			return null;
		}

		$versionProvide = new VersionConstraint('==', $this->getVersion($package));
		foreach ($this->_globalMetadata[$package->getName()] as $data) {
			$versionRequire = $this->versionParser->parseConstraints($data['version']);
			if ($versionRequire->matches($versionProvide)) {
				return $data['metadata'];
			}
		}
	}

	/**
	 * @return mixed
	 */
	private function getLockFileData()
	{
		if ($this->_lockFileData === null) {
			$this->_lockFileData = array();
			$data = Json::decode(file_get_contents(dirname($this->libsDir) . '/composer.lock'), Json::FORCE_ARRAY);

			foreach ($data['packages'] as $package) {
				$package['version'] = $this->versionParser->normalize($package['version']);
				$this->_lockFileData[$package['name']] = $package;
			}
		}

		return $this->_lockFileData;
	}

	/**
	 * Registration of package.
	 *
	 * @param \Venne\Packages\IPackage $package
	 */
	private function register(IPackage $package)
	{
		$packageConfig = &$this->getPackageConfig();
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
	 * @param \Venne\Packages\IPackage $package
	 */
	private function unregister(IPackage $package)
	{
		$packageConfig = &$this->getPackageConfig();
		unset($packageConfig[$package->getName()]);
		$this->savePackageConfig();
		$this->onUnregister($this, $package);

	}

}

