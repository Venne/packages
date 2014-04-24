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
 * Defines an absence of constraints
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class EmptyConstraint implements ILinkConstraint
{

	public function matches(ILinkConstraint $provider)
	{
		return TRUE;
	}


	public function __toString()
	{
		return '[]';
	}

}
