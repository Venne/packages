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

use Nette\InvalidArgumentException;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
final class PathResolver
{

	/** @var array */
	private $packages;


	/**
	 * @param $packages
	 */
	public function __construct($packages)
	{
		$this->packages = & $packages;
	}


	/**
	 * Expands @foo/path/....
	 *
	 * @static
	 * @param $path
	 * @param string|NULL $localPrefix
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function expandPath($path, $localPrefix = '')
	{
		$path = \Nette\Utils\Strings::replace($path, '~\\\~', '/');

		if (substr($path, 0, 1) !== '@') {
			return $path;
		}

		$pos = strpos($path, '/');
		if ($pos) {
			$package = substr($path, 1, $pos - 1);
		} else {
			$package = substr($path, 1);
		}

		$package = str_replace('.', '/', $package);

		if (!isset($this->packages[$package])) {
			throw new InvalidArgumentException("Package '{$package}' does not exist.");
		}

		$path = $this->packages[$package]['path'] . ($localPrefix ? '/' . $localPrefix : '') . ($pos ? substr($path, $pos) : '');
		return \Nette\Utils\Strings::replace($path, '~\\\~', '/');
	}


	/**
	 * Expands @foo/path/....
	 *
	 * @static
	 * @param $path
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function expandResource($path)
	{
		if (substr($path, 0, 1) !== '@') {
			return $path;
		}

		$pos = strpos($path, '/');
		if ($pos) {
			$package = substr($path, 1, $pos - 1);
		} else {
			$package = substr($path, 1);
		}

		$package = str_replace('.', '/', $package);

		if (!isset($this->packages[$package])) {
			throw new InvalidArgumentException("Package '{$package}' does not exist.");
		}

		return 'resources/' . $package . ($pos ? substr($path, $pos) : '');
	}
}

