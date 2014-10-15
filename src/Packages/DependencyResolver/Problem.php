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

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class Problem extends Object
{

	/** @var \Venne\Packages\DependencyResolver\Job[] */
	private $solutions = array();

	/**
	 * @param \Venne\Packages\DependencyResolver\Job $job
	 */
	public function addSolution(Job $job)
	{
		if ($this->hasSolution($job)) {
			throw new InvalidArgumentException(sprintf(
				'Solution \'%s:%s\' is already added.',
				$job->getPackage()->getName(),
				$job->getAction()
			));
		}

		$this->solutions[$job->getPackage()->getName()] = $job;
	}

	/**
	 * @param \Venne\Packages\DependencyResolver\Job $job
	 * @return bool
	 */
	public function hasSolution(Job $job)
	{
		return isset($this->solutions[$job->getPackage()->getName()]);
	}

	/**
	 * @return \Venne\Packages\DependencyResolver\Job[]
	 */
	public function getSolutions()
	{
		return array_merge($this->solutions);
	}

}
