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
 * Defines a conjunctive or disjunctive set of constraints on the target of a package link
 *
 * @author Nils Adermann <naderman@naderman.de>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class MultiConstraint implements ILinkConstraint
{

	/** @var \Venne\Packages\LinkConstraint\ILinkConstraint[] */
	protected $constraints;

	/** @var bool */
	protected $conjunctive;

	/**
	 * Sets operator and version to compare a package with
	 *
	 * @param array $constraints A set of constraints
	 * @param bool $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
	 */
	public function __construct(array $constraints, $conjunctive = true)
	{
		$this->constraints = $constraints;
		$this->conjunctive = $conjunctive;
	}

	/**
	 * @param \Venne\Packages\LinkConstraint\ILinkConstraint $provider
	 * @return bool
	 */
	public function matches(ILinkConstraint $provider)
	{
		if (false === $this->conjunctive) {
			foreach ($this->constraints as $constraint) {
				if ($constraint->matches($provider)) {
					return true;
				}
			}

			return false;
		}

		foreach ($this->constraints as $constraint) {
			if (!$constraint->matches($provider)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$constraints = array();
		foreach ($this->constraints as $constraint) {
			$constraints[] = $constraint->__toString();
		}

		return '[' . implode($this->conjunctive ? ', ' : ' | ', $constraints) . ']';
	}

}
