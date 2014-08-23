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
use Venne\Packages\PathResolver;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class PathResolverTest extends TestCase
{

	/** @var \Venne\Packages\PathResolver */
	protected $pathResolver;

	/** @var mixed */
	protected $parameters = array(
		'venne/foo' => array('path' => '/foo'),
		'venne/bar' => array('path' => '/modules/bar'),
		'venne/win' => array('path' => 'C:\\win'),
	);

	protected function setUp()
	{
		parent::setUp();

		$this->pathResolver = new PathResolver($this->parameters);
	}

	/**
	 * @return mixed
	 */
	public static function dataExpandPath()
	{
		return array(
			array('a/b/c', 'a/b/c'),
			array('/a/b/c', '/a/b/c'),
			array('/foo', '@venne.foo'),
			array('/foo/test', '@venne.foo/test'),
			array('/modules/bar', '@venne.bar'),
			array('/modules/bar/test', '@venne.bar/test'),
			array('C:/foo/bar', 'C:\\foo\\bar'),
			array('/foo/foo/bar', '@venne.foo\\foo\\bar'),
			array('C:/win/foo/bar', '@venne.win\\foo\\bar'),
		);
	}

	/**
	 * @dataProvider dataExpandPath
	 *
	 * @param string $expect
	 * @param string $path
	 */
	public function testExpandPath($expect, $path)
	{
		Assert::equal($expect, $this->pathResolver->expandPath($path));
	}

	public function testExpandPathException()
	{
		$pathResolver = $this->pathResolver;
		Assert::exception(function () use ($pathResolver) {
			$pathResolver->expandPath('@cms/foo');
		}, 'Nette\InvalidArgumentException');
	}

	/**
	 * @return mixed
	 */
	public static function dataExpandResource()
	{
		return array(
			array('a/b/c', 'a/b/c'),
			array('/a/b/c', '/a/b/c'),
			array('resources/venne/foo', '@venne.foo'),
			array('resources/venne/foo/test', '@venne.foo/test'),
			array('resources/venne/bar', '@venne.bar'),
			array('resources/venne/bar/test', '@venne.bar/test'),
		);
	}

	/**
	 * @dataProvider dataExpandResource
	 *
	 * @param string $expect
	 * @param string $path
	 */
	public function testExpandResource($expect, $path)
	{
		Assert::equal($expect, $this->pathResolver->expandResource($path));
	}
}

$testCache = new PathResolverTest;
$testCache->run();
