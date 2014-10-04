<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\DependencyResolver;

use Nette\InvalidArgumentException;
use Nette\Object;
use Venne\Packages\IPackage;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class Job extends Object
{

	const ACTION_INSTALL = 'install';

	const ACTION_UNINSTALL = 'uninstall';

	/** @var string */
	private $action;

	/** @var \Venne\Packages\IPackage */
	private $package;

	/** @var array */
	private static $actions = array(
		self::ACTION_INSTALL => true,
		self::ACTION_UNINSTALL => true,
	);

	/**
	 * @param string $action
	 * @param \Venne\Packages\IPackage $package
	 */
	public function __construct($action, IPackage $package)
	{
		if (!isset(self::$actions[$action])) {
			throw new InvalidArgumentException(sprintf(
				'Action must be one of \'%s\'. \'%s\' is given.',
				join(', ', self::$actions),
				$action
			));
		}

		$this->action = $action;
		$this->package = $package;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return IPackage
	 */
	public function getPackage()
	{
		return $this->package;
	}
}
