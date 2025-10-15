<?php

namespace Civi\CivicrmExtensionPlugin\Command;

use Composer\Command\BaseCommand;
use Civi\CivicrmExtensionPlugin\Handler;
use Civi\CivicrmExtensionPlugin\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command class for 'civicrm:download-extensions' command.
 */
class CivicrmDownloadExtensionsCommand extends BaseCommand {

  /**
   * Function to define composer command for extension download.
   */
  protected function configure() {
    parent::configure();

    $this->setName('civicrm:download-extensions')
      ->setDescription('Download CiviCRM extensions defined in composer.json')
      ->addOption('clean', 'c', InputOption::VALUE_NONE, 'clean extension directory.');
  }

  /**
   * Function to execute the composer command.
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $cleanExtDir = FALSE;
    if ($input->hasParameterOption('--clean', TRUE) || $input->hasParameterOption('-c', TRUE)) {
      $cleanExtDir = TRUE;
    }
    $this->createHandler()->downloadCivicrmExtensions($cleanExtDir);
    $this->createHandler()->syncWebAssetsToWebRoot();

    return 0;
  }

  /**
   * Creates a new handler object.
   *
   * @return \Civi\CivicrmExtensionPlugin\Handler
   *   A new handler service.
   */
  protected function createHandler() {
    $filesystem = new Filesystem();
    $util = new Util($filesystem);

    return new Handler($this->getComposer(), $this->getIO(), $filesystem, $util);
  }

}
