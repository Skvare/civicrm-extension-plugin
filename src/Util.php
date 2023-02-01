<?php

namespace Civi\CivicrmExtensionPlugin;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Some utility methods to perform download operations.
 *
 *  @package Civi\CivicrmExtensionPlugin
 *
 * Composer plugin Util to add support for CiviCRM.
 */
class Util {

  /**
   * Filesystem object.
   *
   * @var \Composer\Util\Filesystem
   */
  protected $filesystem;

  /**
   * Util constructor.
   */
  public function __construct(Filesystem $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   * Remove a directory recursively.
   *
   * @param string $dir
   *   The directory.
   *  @param bool $keeCurrentDir
   *   Keep current dir or not.
   */
  public function removeDirectoryRecursively($dir, $keeCurrentDir = FALSE) {
    if (!file_exists($dir)) {
      return;
    }

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
      if ($keeCurrentDir && $fileinfo->getFilename() == '.gitkeep') {
        continue;
      }
      $this->filesystem->remove($fileinfo->getRealPath());
    }

    if (!$keeCurrentDir) {
      $this->filesystem->remove($dir);
    }
  }

}
