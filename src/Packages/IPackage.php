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

	public function getName();


	public function getDescription();


	public function getKeywords();


	public function getLicense();


	public function getAuthors();


	public function getRequire();


	public function getConfiguration();


	public function getPath();


	public function getRelativePublicPath();


	public function getInstallers();

}

