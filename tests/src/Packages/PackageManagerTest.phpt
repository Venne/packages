<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace VenneTests\Packages;

use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;
use Venne\Packages\Caching\CacheManager;
use Venne\Packages\PackageManager;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class PackageManagerTest extends TestCase
{

	/** @var PackageManager */
	protected $packageManager;


	protected function setUp()
	{
		parent::setUp();

		$this->packageManager = new PackageManager(
			new Container(),
			__DIR__ . '/config',
			__DIR__ . '/vendor',
			__DIR__,
			TEMP_DIR,
			array('.Venne.php', 'Package.php')
		);
	}


	/**
	 * @return array
	 */
	public static function dataGetFormattedPath()
	{
		return array(
			array('/foo/bar', '/foo/bar'),
			array('%libsDir%/foo', __DIR__ . '/vendor\foo'),
		);
	}


	/**
	 * @dataProvider dataGetFormattedPath
	 *
	 * @param string $expect
	 * @param string $path
	 */
	public function testGetFormattedPath($expect, $path)
	{
		$class = new \ReflectionClass('Venne\Packages\PackageManager');
		$method = $class->getMethod('getFormattedPath');
		$method->setAccessible(true);

		Assert::equal($expect, $method->invoke($this->packageManager, $path));
	}

}

$testCache = new PackageManagerTest;
$testCache->run();
