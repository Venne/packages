<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace VenneTests\Packages\LinkConstraint;

use Tester\Assert;
use Tester\TestCase;
use Venne\Packages\LinkConstraint\MultiConstraint;
use Venne\Packages\LinkConstraint\VersionConstraint;

require __DIR__ . '/../../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class MultiConstraintTest extends TestCase
{

	public function testMultiVersionMatchSucceeds()
	{
		$versionRequireStart = new VersionConstraint('>', '1.0');
		$versionRequireEnd = new VersionConstraint('<', '1.2');
		$versionProvide = new VersionConstraint('==', '1.1');

		$multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

		Assert::true($multiRequire->matches($versionProvide));
	}

	public function testMultiVersionProvidedMatchSucceeds()
	{
		$versionRequireStart = new VersionConstraint('>', '1.0');
		$versionRequireEnd = new VersionConstraint('<', '1.2');
		$versionProvideStart = new VersionConstraint('>=', '1.1');
		$versionProvideEnd = new VersionConstraint('<', '2.0');

		$multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));
		$multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

		Assert::true($multiRequire->matches($multiProvide));
	}

	public function testMultiVersionMatchFails()
	{
		$versionRequireStart = new VersionConstraint('>', '1.0');
		$versionRequireEnd = new VersionConstraint('<', '1.2');
		$versionProvide = new VersionConstraint('==', '1.2');

		$multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

		Assert::false($multiRequire->matches($versionProvide));
	}

}

$testCache = new MultiConstraintTest;
$testCache->run();
