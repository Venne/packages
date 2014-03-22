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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Venne\Packages\PackageManager;

/**
 * Command to execute DQL queries in a given EntityManager.
 */
class SyncCommand extends Command
{

	/** @var PackageManager */
	protected $packageManager;


	/**
	 * @param \Venne\Packages\PackageManager $packageManager
	 */
	public function __construct(PackageManager $packageManager)
	{
		parent::__construct();

		$this->packageManager = $packageManager;
	}


	/**
	 * @see Console\Command\Command
	 */
	protected function configure()
	{
		$this
			->setName('sync')
			->addOption('composer', NULL, InputOption::VALUE_NONE, 'run as composer script')
			->setDescription('Synchronize packages.');
	}


	/**
	 * @see Console\Command\Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($input->getOption('composer')) {
			$output->writeln("+---------------------------------+");
			$output->writeln("| Package manager synchronization |");
			$output->writeln("+---------------------------------+");
		}

		// register available
		foreach ($this->packageManager->registerAvailable() as $item) {
			foreach ($item as $name => $action) {
				$output->writeln("<info>{$action} : {$name}</info>");
			}
		}

		try {
			foreach ($this->packageManager->installAvailable() as $item) {
				foreach ($item as $name => $action) {
					$output->writeln("<info>{$action} : {$name}</info>");
				}
			}

			foreach ($this->packageManager->uninstallAbsent() as $item) {
				foreach ($item as $name => $action) {
					$output->writeln("<info>{$action} : {$name}</info>");
				}
			}

		} catch (InvalidArgumentException $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
