<?php

namespace Civi\CivicrmExtensionPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Handler service does all the actual work of the plugin. :-)
 */
class Handler {

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * File system object.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $filesystem;

  /**
   * Util Object.
   *
   * @var \Civi\CivicrmExtensionPlugin\Util
   */
  protected $util;

  /**
   * Handler constructor.
   *
   * @param \Composer\Composer $composer
   *   The composer object.
   * @param \Composer\IO\IOInterface $io
   *   The composer I/O object.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem service.
   * @param \Civi\CivicrmExtensionPlugin\Util $util
   *   The util service.
   */
  public function __construct(Composer $composer, IOInterface $io, Filesystem $filesystem, Util $util) {
    $this->composer = $composer;
    $this->io = $io;
    $this->filesystem = $filesystem;
    $this->util = $util;
  }

  /**
   * Gets the path to the CiviCRM code.
   *
   * @return string
   *   CiviCRM Core library path.
   */
  protected function getCivicrmCorePath() {
    $vendor_path = $this->composer->getConfig()->get('vendor-dir');

    return "{$vendor_path}/civicrm/civicrm-core";
  }

  /**
   * Gets the CiviCRM core version.
   *
   * @param \Composer\Package\Package|null $package
   *   The package that was just installed or updated.
   *
   * @return mixed
   *   Get CiviCRM Version from core file.
   */
  protected function getCivicrmCoreVersion(Package $package = NULL) {
    if (!$package) {
      $package = $this->getCivicrmCorePackage();
      if (!$package) {
        throw new \RuntimeException("The civicrm/civicrm-core package doesn't appear to be installed. Did you forget to run 'composer require civicrm/civicrm-core'?");
      }
    }

    if (preg_match('/(\d+\.\d+\.\d+)/', $package->getPrettyVersion(), $matches)) {
      $civicrm_version = $matches[1];
    }
    else {
      // @todo Allow the user to give a version number.
      throw new \RuntimeException("Unable to determine CiviCRM release version from {$package->getPrettyVersion()}");
    }

    return $civicrm_version;
  }

  /**
   * Gets the currently installed CiviCRM core package.
   *
   * @return \Composer\Package\Package
   *   The package.
   */
  protected function getCivicrmCorePackage() {
    /** @var \Composer\Repository\RepositoryManager $repository_manager */
    $repository_manager = $this->composer->getRepositoryManager();

    /** @var \Composer\Repository\RepositoryInterface $local_repository */
    $local_repository = $repository_manager->getLocalRepository();

    /** @var \Composer\Package\Package $package */
    foreach ($local_repository->getPackages() as $package) {
      if ($package->getName() == 'civicrm/civicrm-core') {
        return $package;
      }
    }

    throw new \RuntimeException("Unable to find civicrm/civicrm-core package");
  }

  /**
   * Does all the stuff we want to do after CiviCRM has been installed.
   */
  public function afterCivicrmInstallOrUpdate(Package $civicrm_package = NULL) {
    $this->downloadCivicrmExtensions();
  }

  /**
   * Outputs a message to the user.
   *
   * @param string $message
   *   The message.
   * @param bool $newline
   *   Whether or not to add a newline.
   * @param int $verbosity
   *   The verbosity.
   */
  protected function output($message, $newline = TRUE, $verbosity = IOInterface::NORMAL) {
    $this->io->write("> [civicrm-extension-plugin] {$message}", $newline, $verbosity);
  }

  /**
   * Download CiviCRM extensions based on configuration in 'extra'.
   */
  public function downloadCivicrmExtensions() {
    /** @var \Composer\Package\RootPackageInterface $package */
    $package = $this->composer->getPackage();
    $extra = $package->getExtra();

    if (!empty($extra['civicrm']['extensions'])) {
      $extensions_dir = $extra['civicrm']['extensions_dir'] ?? '';
      // Get extension dir path from composer file.
      $extensions_install_path = $extra['civicrm']['extensions_install_path'];
      // If path no exist then use default path.
      if (empty($extensions_install_path)) {
        $extensions_install_path = './web/sites/default/civicrm/extensions/contrib';
      }
      // local_extension.php must be git ignored. file should be present in
      // root directory.
      // e.g.
      /*
      <?php
      // used when you have list of common extension are present. Used to
      create soft link.
      // overwrite extension directory path as per you local setup
      $extensions_dir = 'PATH_TO_EXTENSION/civicrm-extensions/';
      // overwrite extension install path as per you local setup
      $extensions_install_path = '
      ./web/sites/default/civicrm/extensions/contrib';
       */
      if (file_exists('./local_extension.php')) {
        include_once './local_extension.php';
      }
      // Create extension directory path if not exit.
      if (!$this->filesystem->exists($extensions_install_path)) {
        $this->filesystem->mkdir($extensions_install_path);
      }

      // Make sure path end with DIRECTORY_SEPARATOR.
      $extensions_dir = rtrim($extensions_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      $extensions_dir_core = getcwd() . '/vendor/civicrm/civicrm-core/ext/';
      foreach ($extra['civicrm']['extensions'] as $name => $info) {
        if (!is_array($info)) {
          $info = ['url' => $info];
        }
        if (!array_key_exists('url', $info)) {
          $info['url'] = NULL;
        }
        if (!isset($info['patches']) || !is_array($info['patches'])) {
          $info['patches'] = [];
        }
        if (!isset($info['link'])) {
          $info['link'] = NULL;
        }
        if (!empty($info['type']) && $info['type'] == 'core') {
          $extensions_dir_path = $extensions_dir_core;
        }
        else {
          $extensions_dir_path = $extensions_dir;
        }
        $this->downloadCivicrmExtension($extensions_install_path, $name, $info['url'], $info['patches'], $extensions_dir_path, $info['link']);
      }
    }
  }

  /**
   * Download a single CiviCRM extension.
   *
   * @param string $extension_path
   *   The extension install path.
   * @param string $name
   *   The extension name.
   * @param string $url
   *   The URL to the zip archive.
   * @param string[] $patches
   *   A list of patches to apply to the extension.
   * @param string $extensionDir
   *   The Local Directory Path.
   * @param string $link
   *   The Local Extension Directory Name for soft link.
   */
  protected function downloadCivicrmExtension($extension_path,
                                              $name,
                                              $url,
                                              array $patches,
                                              $extensionDir = NULL,
                                              $link = NULL) {
    $destination_path = "{$extension_path}/{$name}";

    // Check link is present.
    if (!empty($link)) {
      // Final path.
      $link = $extensionDir . $link;
      if (file_exists($link)) {
        $this->output("<info>creating soft link for  {$link} to {$destination_path}...</info>");
        $this->filesystem->remove($destination_path);
        try {
          $this->filesystem->symlink($link, $destination_path);

          return;
        }
        catch (IOException $exception) {
          $this->output("<error>Failed to create soft link for {$link} to {$destination_path}...</error>");
        }
      }
      else {
        $this->output("<warning>Target directory {$link} for {$name} not exist...</warning>");
      }
    }
    // Remove any old copies of the extension laying around.
    if ($this->filesystem->exists($destination_path)) {
      $this->util->removeDirectoryRecursively($destination_path);
    }
    if (empty($url)) {
      $this->output("<error>Download Url missing for {$destination_path}...</error>");

      return;
    }
    $extension_archive_file = tempnam(sys_get_temp_dir(), "drupal-civicrm-extension-");
    $this->output("<info>Downloading CiviCRM extension {$name} from {$url}...</info>");
    $this->filesystem->dumpFile($extension_archive_file, fopen($url, 'r'));
    // Extract the zip archive (recording the first file to figure out what
    // path it extracts to).
    $firstFile = NULL;
    try {
      $zip = new \ZipArchive();
      $zip->open($extension_archive_file);
      $firstFile = $zip->getNameIndex(0);
      $zip->extractTo($extension_path);
      $zip->close();
    }
    finally {
      $this->filesystem->remove($extension_archive_file);
    }

    // If the extension directory wasn't named like the extension name, then
    // attempt to rename it.
    if (!$this->filesystem->exists($destination_path)) {
      $parts = explode('/', $firstFile);
      if (count($parts) > 1) {
        $this->filesystem->rename("{$extension_path}/{$parts[0]}", $destination_path);
      }
    }

    // If there are any patches for this extension.
    if (!empty($patches)) {
      foreach ($patches as $patch) {
        $this->output("|-> Applying patch: $patch");
        $process = new Process("patch -p1", $destination_path);
        $process->setInput(file_get_contents($patch));
        $process->mustRun();
      }
    }
  }

}
