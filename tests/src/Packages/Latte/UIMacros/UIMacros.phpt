<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace VenneTests\Packages\Latte\UIMacros;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\MacroTokens;
use Latte\PhpWriter;
use Tester\Assert;
use Venne\Packages\Latte\Macros\UIMacros;
use Venne\Packages\PathResolver;

require __DIR__ . '/../../../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class UIMacrosTest extends \Tester\TestCase
{

	/** @var \Venne\Packages\Latte\Macros\UIMacros */
	private $macros;

	public function setUp()
	{
		$compiler = new Compiler;
		$pathResolver = new PathResolver(array(
			'foo' => array('path' => '/foopath'),
		));

		$this->macros = new UIMacros($compiler);
		$this->macros->injectPathResolver($pathResolver);
	}

	/**
	 * @return string[][]
	 */
	public function dataServiceNamesAndPresenters()
	{
		return array(
			array('foo.latte', 'foo.latte'),
			array('@foo/@layout.latte', '/foopath/Resources/templates/@layout.latte'),
		);
	}

	/**
	 * @dataProvider dataServiceNamesAndPresenters
	 *
	 * @param string $path
	 * @param string $expect
	 */
	public function testBlockPath($path, $expect)
	{
		Assert::same($expect, $this->getMacroExtends($path)->args);
		Assert::same($expect, $this->getMacroIncludeBlock($path)->args);
	}

	public function testBlockPathException()
	{
		$_this = $this;
		Assert::exception(function () use ($_this) {
			$_this->getMacroExtends('@bar/page.latte')->args;
		}, 'Nette\InvalidArgumentException');
		Assert::exception(function () use ($_this) {
			$_this->getMacroIncludeBlock('@bar/page.latte')->args;
		}, 'Nette\InvalidArgumentException');
	}

	/**
	 * @param string $path
	 * @return \Latte\MacroNode
	 */
	public function getMacroExtends($path)
	{
		$macroNode = new MacroNode($this->macros, '', $path);
		$phpWriter = new PhpWriter(new MacroTokens(''));

		$this->macros->macroExtends($macroNode, $phpWriter);
		return $macroNode;
	}

	/**
	 * @param string $path
	 * @return \Latte\MacroNode
	 */
	public function getMacroIncludeBlock($path)
	{
		$macroNode = new MacroNode($this->macros, '', $path);
		$phpWriter = new PhpWriter(new MacroTokens(''));

		$this->macros->macroIncludeBlock($macroNode, $phpWriter);
		return $macroNode;
	}
}

$testCache = new UIMacrosTest;
$testCache->run();
