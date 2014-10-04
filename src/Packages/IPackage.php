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

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
interface IPackage
{

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getDescription();

	/**
	 * @return string[]
	 */
	public function getKeywords();

	/**
	 * @return string[]
	 */
	public function getLicense();

	/**
	 * @return string[][]
	 */
	public function getAuthors();

	/**
	 * @return string[]
	 */
	public function getRequire();

	/**
	 * @return mixed
	 */
	public function getConfiguration();

	/**
	 * @return string
	 */
	public function getPath();

	/**
	 * @return string
	 */
	public function getRelativePublicPath();

	/**
	 * @return string[]
	 */
	public function getInstallers();

}
