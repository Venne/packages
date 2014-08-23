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
use Venne\Packages\LinkConstraint\VersionConstraint;

require __DIR__ . '/../../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class VersionConstraintTest extends TestCase
{

	/**
	 * @return string[][]
	 */
	public static function successfulVersionMatches()
	{
		return array(
			//    require    provide
			array('==', '1', '==', '1'),
			array('>=', '1', '>=', '2'),
			array('>=', '2', '>=', '1'),
			array('>=', '2', '>', '1'),
			array('<=', '2', '>=', '1'),
			array('>=', '1', '<=', '2'),
			array('==', '2', '>=', '2'),
			array('!=', '1', '!=', '1'),
			array('!=', '1', '==', '2'),
			array('!=', '1', '<', '1'),
			array('!=', '1', '<=', '1'),
			array('!=', '1', '>', '1'),
			array('!=', '1', '>=', '1'),
			array('==', 'dev-foo-bar', '==', 'dev-foo-bar'),
			array('==', 'dev-foo-xyz', '==', 'dev-foo-xyz'),
			array('>=', 'dev-foo-bar', '>=', 'dev-foo-xyz'),
			array('<=', 'dev-foo-bar', '<', 'dev-foo-xyz'),
			array('!=', 'dev-foo-bar', '<', 'dev-foo-xyz'),
			array('>=', 'dev-foo-bar', '!=', 'dev-foo-bar'),
			array('!=', 'dev-foo-bar', '!=', 'dev-foo-xyz'),
		);
	}

	/**
	 * @dataProvider successfulVersionMatches
	 */
	public function testVersionMatchSucceeds($requireOperator, $requireVersion, $provideOperator, $provideVersion)
	{
		$versionRequire = new VersionConstraint($requireOperator, $requireVersion);
		$versionProvide = new VersionConstraint($provideOperator, $provideVersion);

		Assert::true($versionRequire->matches($versionProvide));
	}

	/**
	 * @return string[][]
	 */
	public static function failingVersionMatches()
	{
		return array(
			//    require    provide
			array('==', '1', '==', '2'),
			array('>=', '2', '<=', '1'),
			array('>=', '2', '<', '2'),
			array('<=', '2', '>', '2'),
			array('>', '2', '<=', '2'),
			array('<=', '1', '>=', '2'),
			array('>=', '2', '<=', '1'),
			array('==', '2', '<', '2'),
			array('!=', '1', '==', '1'),
			array('==', '1', '!=', '1'),
			array('==', 'dev-foo-dist', '==', 'dev-foo-zist'),
			array('==', 'dev-foo-bist', '==', 'dev-foo-aist'),
			array('<=', 'dev-foo-bist', '>=', 'dev-foo-aist'),
			array('>=', 'dev-foo-bist', '<', 'dev-foo-aist'),
			array('<', '0.12', '==', 'dev-foo'), // branches are not comparable
			array('>', '0.12', '==', 'dev-foo'), // branches are not comparable
		);
	}

	/**
	 * @dataProvider failingVersionMatches
	 */
	public function testVersionMatchFails($requireOperator, $requireVersion, $provideOperator, $provideVersion)
	{
		$versionRequire = new VersionConstraint($requireOperator, $requireVersion);
		$versionProvide = new VersionConstraint($provideOperator, $provideVersion);

		Assert::false($versionRequire->matches($versionProvide));
	}

	public function testComparableBranches()
	{
		$versionRequire = new VersionConstraint('>', '0.12');
		$versionProvide = new VersionConstraint('==', 'dev-foo');

		Assert::false($versionRequire->matches($versionProvide));
		Assert::false($versionRequire->matchSpecific($versionProvide, true));

		$versionRequire = new VersionConstraint('<', '0.12');
		$versionProvide = new VersionConstraint('==', 'dev-foo');

		Assert::false($versionRequire->matches($versionProvide));
		Assert::true($versionRequire->matchSpecific($versionProvide, true));
	}

}

$testCache = new VersionConstraintTest;
$testCache->run();
