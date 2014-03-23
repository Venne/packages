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

use Tester\Assert;
use Tester\TestCase;
use Venne\Packages\Helpers;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class HelpersTest extends TestCase
{


	/**
	 * @return array
	 */
	public static function dataGetRelativePath()
	{
		return array(
			array('../a', array('/foo/bar', '/foo/a')),
			array('../a', array('./foo/bar', './foo/a')),
		);
	}


	/**
	 * @dataProvider dataGetRelativePath
	 *
	 * @param string $expect
	 * @param string $path
	 */
	public function testGetRelativePath($expect, $path)
	{
		Assert::equal($expect, Helpers::getRelativePath($path[0], $path[1], (isset($path[2]) ? $path[2] : NULL)));
	}


	public function testGetClassesFromFile()
	{
		Assert::equal(array('Class1'), Helpers::getClassesFromFile(__DIR__ . '/Helpers.class1.php'));
		Assert::equal(array('Foo\Bar\Class2'), Helpers::getClassesFromFile(__DIR__ . '/Helpers.class2.php'));
	}

}

$testCache = new HelpersTest;
$testCache->run();
