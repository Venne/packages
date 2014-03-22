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

use Nette\Object;
use Nette\Utils\Json;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
abstract class Package extends Object implements IPackage
{

	/** @var array */
	protected $composerData;


	/**
	 * @return string
	 */
	public function getName()
	{
		$this->loadComposerData();

		return $this->composerData['name'];
	}


	/**
	 * @return mixed
	 */
	public function getDescription()
	{
		$this->loadComposerData();

		return isset($this->composerData['description']) ? $this->composerData['description'] : NULL;
	}


	/**
	 * @return array
	 */
	public function getKeywords()
	{
		$this->loadComposerData();

		if (isset($this->composerData['keywords'])) {
			$keywords = $this->composerData['keywords'];
			return is_array($keywords) ? $keywords : array_map('trim', explode(',', $keywords));
		}
	}


	/**
	 * @return array
	 */
	public function getLicense()
	{
		$this->loadComposerData();

		return isset($this->composerData['license']) ? $this->composerData['license'] : NULL;
	}


	/**
	 * @return array
	 */
	public function getAuthors()
	{
		$this->loadComposerData();

		return isset($this->composerData['authors']) ? $this->composerData['authors'] : NULL;
	}


	/**
	 * @return array
	 */
	public function getRequire()
	{
		$this->loadComposerData();

		$ret = array();
		if (isset($this->composerData['require'])) {
			foreach ($this->composerData['require'] as $name => $require) {
				if (strpos($name, '/') !== FALSE) {
					$ret[] = $name;
				}
			}
		}

		return $ret;
	}


	/**
	 * @return string
	 */
	public function getRelativePublicPath()
	{
		$this->loadComposerData();

		if (isset($this->composerData['extra']['venne']['relativePublicPath'])) {
			return $this->composerData['extra']['venne']['relativePublicPath'];
		}

		return file_exists($this->getPath() . '/Resources/public') ? '/Resources/public' : NULL;
	}


	/**
	 * @return array
	 */
	public function getConfiguration()
	{
		$this->loadComposerData();

		if (isset($this->composerData['extra']['venne']['configuration'])) {
			return $this->composerData['extra']['venne']['configuration'];
		}

		return array();
	}


	/**
	 * @return array
	 */
	public function getInstallers()
	{
		$this->loadComposerData();

		$installers = array('Venne\Packages\Installers\StructureInstaller');

		if (isset($this->composerData['extra']['venne']['installers'])) {
			$installers = array_merge($installers, $this->composerData['extra']['venne']['installers']);
		}

		return $installers;
	}


	/**
	 * @return string
	 */
	public function getPath()
	{
		return dirname($this->getReflection()->getFileName());
	}


	private function loadComposerData()
	{
		if ($this->composerData === NULL) {
			$this->composerData = Json::decode(file_get_contents(dirname($this->getReflection()->getFileName()) . '/composer.json'), Json::FORCE_ARRAY);
		}
	}

}

