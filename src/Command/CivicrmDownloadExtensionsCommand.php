<?php

namespace Civi\CivicrmExtensionPlugin\Command;

use Civi\CivicrmExtensionPlugin\Handler;
use Civi\CivicrmExtensionPlugin\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command class for 'civicrm:download-extensions-new' command.
 */
class CivicrmDownloadExtensionsCommand extends \Composer\Command\BaseCommand {

  protected function configure() {
    parent::configure();

    $this->setName('civicrm:download-extensions-new')
      ->setDescription('Download CiviCRM extensions defined in composer.json');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->createHandler()->downloadCivicrmExtensions();
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
