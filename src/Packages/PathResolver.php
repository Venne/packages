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
use Nette\Utils\Strings;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
final class PathResolver
{

	/** @var string[] */
	private $packages;

	/**
	 * @param string[] $packages
	 */
	public function __construct($packages)
	{
		$this->packages = &$packages;
	}

	/**
	 * Expands @foo/path/....
	 *
	 * @param string $path
	 * @param string|null $localPrefix
	 * @return string
	 */
	public function expandPath($path, $localPrefix = '')
	{
		$path = Strings::replace($path, '~\\\~', '/');

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
			throw new InvalidArgumentException(sprintf('Package \'%s\' does not exist.', $package));
		}

		$path = $this->packages[$package]['path'] . ($localPrefix ? '/' . $localPrefix : '') . ($pos ? substr($path, $pos) : '');

		return Strings::replace($path, '~\\\~', '/');
	}

	/**
	 * Expands @foo/path/....
	 *
	 * @param string $path
	 * @return string
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
			throw new InvalidArgumentException(sprintf('Package \'%s\' does not exist.', $package));
		}

		return 'resources/' . $package . ($pos ? substr($path, $pos) : '');
	}

}
