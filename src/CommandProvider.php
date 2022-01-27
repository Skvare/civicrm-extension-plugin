<?php

namespace Civi\CivicrmExtensionPlugin;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Civi\CivicrmExtensionPlugin\Command\CivicrmDownloadExtensionsCommand;

/**
 * Provides all the commands for this plugin.
 */
class CommandProvider implements CommandProviderCapability {

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return [
      new CivicrmDownloadExtensionsCommand(),
    ];
  }

}
