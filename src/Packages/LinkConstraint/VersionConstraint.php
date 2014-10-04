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
 * Provides a common basis for specific package link constraints
 *
 * @author Nils Adermann <naderman@naderman.de>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class VersionConstraint implements ILinkConstraint
{

	/** @var string */
	private $operator;

	/** @var string */
	private $version;

	/**
	 * Sets operator and version to compare a package with
	 *
	 * @param string $operator A comparison operator
	 * @param string $version A version to compare to
	 */
	public function __construct($operator, $version)
	{
		if ('=' === $operator) {
			$operator = '==';
		}

		if ('<>' === $operator) {
			$operator = '!=';
		}

		$this->operator = $operator;
		$this->version = $version;
	}

	/**
	 * @param \Venne\Packages\LinkConstraint\ILinkConstraint $provider
	 * @return bool
	 */
	public function matches(ILinkConstraint $provider)
	{
		if ($provider instanceof MultiConstraint) {
			return $provider->matches($this);
		} elseif ($provider instanceof $this) {
			return $this->matchSpecific($provider);
		}

		return true;
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @param string $operator
	 * @param bool $compareBranches
	 * @return bool|mixed
	 */
	public function versionCompare($a, $b, $operator, $compareBranches = false)
	{
		$aIsBranch = 'dev-' === substr($a, 0, 4);
		$bIsBranch = 'dev-' === substr($b, 0, 4);
		if ($aIsBranch && $bIsBranch) {
			return $operator == '==' && $a === $b;
		}

		// when branches are not comparable, we make sure dev branches never match anything
		if (!$compareBranches && ($aIsBranch || $bIsBranch)) {
			return false;
		}

		return version_compare($a, $b, $operator);
	}

	/**
	 * @param \Venne\Packages\LinkConstraint\VersionConstraint $provider
	 * @param bool $compareBranches
	 * @return bool
	 */
	public function matchSpecific(VersionConstraint $provider, $compareBranches = false)
	{
		static $cache = array();
		if (isset($cache[$this->operator][$this->version][$provider->operator][$provider->version][$compareBranches])) {
			return $cache[$this->operator][$this->version][$provider->operator][$provider->version][$compareBranches];
		}

		return $cache[$this->operator][$this->version][$provider->operator][$provider->version][$compareBranches] = $this->doMatchSpecific($provider, $compareBranches);
	}

	/**
	 * @param \Venne\Packages\LinkConstraint\VersionConstraint $provider
	 * @param bool $compareBranches
	 * @return bool
	 */
	private function doMatchSpecific(VersionConstraint $provider, $compareBranches = false)
	{
		$noEqualOp = str_replace('=', '', $this->operator);
		$providerNoEqualOp = str_replace('=', '', $provider->operator);

		$isEqualOp = '==' === $this->operator;
		$isNonEqualOp = '!=' === $this->operator;
		$isProviderEqualOp = '==' === $provider->operator;
		$isProviderNonEqualOp = '!=' === $provider->operator;

		// '!=' operator is match when other operator is not '==' operator or version is not match
		// these kinds of comparisons always have a solution
		if ($isNonEqualOp || $isProviderNonEqualOp) {
			return !$isEqualOp && !$isProviderEqualOp
			|| $this->versionCompare($provider->version, $this->version, '!=', $compareBranches);
		}

		// an example for the condition is <= 2.0 & < 1.0
		// these kinds of comparisons always have a solution
		if ($this->operator != '==' && $noEqualOp == $providerNoEqualOp) {
			return true;
		}

		if ($this->versionCompare($provider->version, $this->version, $this->operator, $compareBranches)) {
			// special case, e.g. require >= 1.0 and provide < 1.0
			// 1.0 >= 1.0 but 1.0 is outside of the provided interval
			if ($provider->version == $this->version && $provider->operator == $providerNoEqualOp && $this->operator != $noEqualOp) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->operator . ' ' . $this->version;
	}

}
