<?php

namespace Civi\CivicrmExtensionPlugin;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Some utility methods.
 */
class Util {

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $filesystem;

  /**
   * Util constructor.
   *
   */
  public function __construct(Filesystem $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   * Remove a directory recursively.
   *
   * @param string $dir
   *   The directory.
   */
  public function removeDirectoryRecursively($dir) {
    if (!file_exists($dir)) {
      return;
    }

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
      $this->filesystem->remove($fileinfo->getRealPath());
    }

    $this->filesystem->remove($dir);
  }

  /**
   * Mirror a directory, but only files with certain extensions.
   *
   * @param string $source
   * @param string $destination
   * @param string[] $extensions
   */
  public function mirrorFilesWithExtensions($source, $destination, array $extensions) {
    $this->filesystem->mkdir($destination);

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS));

    /** @var \SplFileInfo $fileinfo */
    foreach ($files as $fileinfo) {
      if (!$fileinfo->isDir() && in_array($fileinfo->getExtension(), $extensions)) {
        $destination_path = $destination . '/' . $this->filesystem->makePathRelative($fileinfo->getPath(), $source);
        if (!$this->filesystem->exists($destination_path)) {
          $this->filesystem->mkdir($destination_path);
        }
        $destination_file = $destination_path . $fileinfo->getFilename();
        $this->filesystem->copy($fileinfo->getRealPath(), $destination_file);
      }
    }
  }

}