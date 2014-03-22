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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Venne\Packages\DependencyResolver\Job;
use Venne\Packages\PackageManager;

/**
 * Command to execute DQL queries in a given EntityManager.
 */
class UninstallCommand extends Command
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
			->setName('uninstall')
			->addArgument('package', InputArgument::REQUIRED, 'Package name')
			->addOption('noconfirm', NULL, InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->setDescription('Uninstall package.');
	}


	/**
	 * @see Console\Command\Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// register available
		foreach ($this->packageManager->registerAvailable() as $item) {
			foreach ($item as $name => $action) {
				$output->writeln("<info>{$action} : {$name}</info>");
			}
		}

		$package = $this->packageManager->createInstance($input->getArgument('package'));

		try {
			/** @var $problem Problem */
			$problem = $this->packageManager->testUninstall($package);
		} catch (InvalidArgumentException $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return;
		}

		if (!$input->getOption('noconfirm') && count($problem->getSolutions()) > 0) {
			foreach ($problem->getSolutions() as $job) {
				$output->writeln("<info>{$job->getAction()} : {$job->getPackage()->getName()}</info>");
			}
			$output->writeln("<info>uninstall : {$package->getName()}</info>");

			$dialog = $this->getHelperSet()->get('dialog');
			if (!$dialog->askConfirmation($output, '<question>Continue with this actions? [y/N]</question> ', FALSE)) {
				return;
			}
		}

		try {
			foreach ($problem->getSolutions() as $job) {
				if ($job->getAction() === Job::ACTION_INSTALL) {
					$this->packageManager->install($job->getPackage());
					$output->writeln("Package '{$job->getPackage()->getName()}' has been installed.");

				} else if ($job->getAction() === Job::ACTION_UNINSTALL) {
					$this->packageManager->uninstall($job->getPackage());
					$output->writeln("Package '{$job->getPackage()->getName()}' has been uninstalled.");

				}
			}
			$this->packageManager->uninstall($package);
			$output->writeln("Package '{$input->getArgument('package')}' has been uninstalled.");
		} catch (InvalidArgumentException $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
