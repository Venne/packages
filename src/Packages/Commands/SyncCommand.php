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
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class SyncCommand extends Command
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
			->setName('sync')
			->addOption('composer', null, InputOption::VALUE_NONE, 'run as composer script')
			->setDescription('Synchronize packages.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($input->getOption('composer')) {
			$output->writeln('+---------------------------------+');
			$output->writeln('| Package manager synchronization |');
			$output->writeln('+---------------------------------+');
		}

		// register available
		foreach ($this->packageManager->registerAvailable() as $item) {
			foreach ($item as $name => $action) {
				$output->writeln(sprintf('<info>%s : %s</info>', $action, $name));
			}
		}

		try {
			foreach ($this->packageManager->installAvailable() as $item) {
				foreach ($item as $name => $action) {
					$output->writeln(sprintf('<info>%s : %s</info>', $action, $name));
				}
			}

			foreach ($this->packageManager->uninstallAbsent() as $item) {
				foreach ($item as $name => $action) {
					$output->writeln(sprintf('<info>%s : %s</info>', $action, $name));
				}
			}

		} catch (InvalidArgumentException $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
		}
	}

}
