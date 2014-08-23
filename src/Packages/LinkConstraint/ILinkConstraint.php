<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\LinkConstraint;

/**
 * Defines a constraint on a link between two packages.
 *
 * @author Nils Adermann <naderman@naderman.de>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
interface ILinkConstraint
{

	/**
	 * @param \Venne\Packages\LinkConstraint\ILinkConstraint $provider
	 * @return bool
	 */
	public function matches(ILinkConstraint $provider);

	/**
	 * @return string
	 */
	public function __toString();

}
