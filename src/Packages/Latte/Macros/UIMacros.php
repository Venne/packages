<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\Latte\Macros;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\BlockMacros;
use Latte\Macros\MacroSet;
use Latte\MacroTokens;
use Latte\PhpWriter;
use Venne\Packages\PathResolver;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class UIMacros extends MacroSet
{

	/** @var PathResolver */
	private $pathResolver;

	/** @var \Latte\Macros\UIMacros */
	private $blockMacros;


	/**
	 * @param PathResolver $pathResolver
	 */
	public function injectPathResolver(PathResolver $pathResolver)
	{
		$this->pathResolver = $pathResolver;
		$this->blockMacros = new BlockMacros($this->getCompiler());
	}


	/**
	 * @param Compiler $compiler
	 * @return static
	 */
	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('includeblock', array($me, 'macroIncludeBlock'));
		$me->addMacro('extends', array($me, 'macroExtends'));
		$me->addMacro('layout', array($me, 'macroExtends'));

		$me->addMacro('path', array($me, 'macroPath'));

		return $me;
	}


	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 */
	public function macroExtends(MacroNode $node, PhpWriter $writer)
	{
		$node->args = $this->pathResolver->expandPath($node->args, 'Resources/templates');
		$node->tokenizer = new MacroTokens($node->args);
		$writer = new PhpWriter($node->tokenizer);
		return $this->blockMacros->macroExtends($node, $writer);
	}


	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroIncludeBlock(MacroNode $node, PhpWriter $writer)
	{
		$node->args = $this->pathResolver->expandPath($node->args, 'Resources/templates');
		$node->tokenizer = new MacroTokens($node->args);
		$writer = new PhpWriter($node->tokenizer);
		return $this->blockMacros->macroIncludeBlock($node, $writer);
	}


	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroPath(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write("echo \$basePath . '/' . \$presenter->context->getByType('Venne\Packages\PathResolver')->expandResource(%node.word)");
	}

}
