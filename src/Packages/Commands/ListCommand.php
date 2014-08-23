<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages\Commands;

use Nette\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venne\Packages\PackageManager;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class ListCommand extends Command
{

	/** @var \Venne\Packages\PackageManager */
	protected $packageManager;

	public function __construct(PackageManager $packageManager)
	{
		parent::__construct();

		$this->packageManager = $packageManager;
	}

	protected function configure()
	{
		$this
			->setName('list')
			->setDescription('List packages.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// register available
		foreach ($this->packageManager->registerAvailable() as $item) {
			foreach ($item as $name => $action) {
				$output->writeln("<info>{$action} : {$name}</info>");
			}
		}

		try {
			foreach ($this->packageManager->getPackages() as $package) {
				$output->writeln(sprintf('<info>%25s</info> | status: <comment>%-12s</comment> | version: <comment>%s</comment>', $package->getName(), $this->packageManager->getStatus($package), $this->packageManager->getVersion($package)));
			}
		} catch (InvalidArgumentException $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}

}
