<?php

namespace Civi\CivicrmExtensionPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Util\ProcessExecutor;
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
   * ProcessExecutor object.
   *
   * @var \Composer\Util\ProcessExecutor
   */
  protected $executor;

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
    $this->executor = new ProcessExecutor($this->io);
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
    $this->syncWebAssetsToWebRoot();
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
   * Syncs web assets from CiviCRM to the web root.
   */
  public function syncWebAssetsToWebRoot() {
    /** @var \Composer\Package\RootPackageInterface $package */
    $package = $this->composer->getPackage();
    $extra = $package->getExtra();
    // No need to sync any file in WordPress.
    if (array_key_exists('cms_type', $extra['civicrm']) &&
      strtolower($extra['civicrm']['cms_type']) == 'wordpress') {
      return;
    }
    $source = $this->getCivicrmCorePath();
    $destination = './web/libraries/civicrm';
    $this->output("<info>Syncing CiviCRM web assets to /web/libraries/civicrm...</info>");
    $vendor_path = $this->composer->getConfig()->get('vendor-dir');
    $this->util->removeDirectoryRecursively("{$destination}/packages/kcfinder");
    if ($this->filesystem->exists("{$vendor_path}/civicrm/civicrm-packages")) {
      $this->filesystem->mirror("{$vendor_path}/civicrm/civicrm-packages/kcfinder", "{$destination}/packages/kcfinder");
      $setting_php_file = '';
    }
    else {
      $this->filesystem->mirror("{$source}/packages/kcfinder", "{$destination}/core/packages/kcfinder");
      $setting_php_file = '/core';
    }
    $this->filesystem->copy("{$vendor_path}/civicrm/civicrm-extension-plugin/src/civicrm.config.php", "{$destination}{$setting_php_file}/civicrm.config.php");
    $this->filesystem->copy("{$source}/extension-compatibility.json", "{$destination}/core/extension-compatibility.json");
    $this->filesystem->copy("{$vendor_path}/civicrm/civicrm-extension-plugin/src/settings_location.txt", "{$destination}{$setting_php_file}/settings_location.php");
    if ($this->filesystem->exists("{$source}/js/wysiwyg/ck-options.json")) {
      $this->filesystem->copy("{$source}/js/wysiwyg/ck-options.json", "{$destination}/core/js/wysiwyg/ck-options.json");
    }
    // Sync rest.php file.
    if ($this->filesystem->exists("{$source}/extern/rest.php")) {
      if (!$this->filesystem->exists("{$destination}/$setting_php_file/extern")) {
        $this->filesystem->mkdir("{$destination}/$setting_php_file/extern");
      }
      $this->filesystem->copy("{$source}/extern/rest.php", "{$destination}/$setting_php_file/extern/rest.php");
    }

    // Civicrm assent plugin not syncing json file.
    if ($this->filesystem->exists("{$source}/ext/ckeditor4/js/ck-options.json")) {
      if (!$this->filesystem->exists("{$destination}/$setting_php_file/ext/ckeditor4/js")) {
        $this->filesystem->mkdir("{$destination}/$setting_php_file/ext/ckeditor4/js");
      }
      $this->filesystem->copy("{$source}/ext/ckeditor4/js/ck-options.json", "{$destination}/$setting_php_file/ext/ckeditor4/js/ck-options.json");
    }

    // Sync civicrm custom css file to the files directory; the same can be used as custom css in civicrm.
    if ($this->filesystem->exists("./patches/civicrm-custom.css")) {
      $this->output("<info>Syncing CiviCRM 'patches/civicrm-custom.css' to 'web/sites/default/files/civicrm-custom.css'</info>");
      $this->filesystem->copy("./patches/civicrm-custom.css", "./web/sites/default/files/civicrm-custom.css", TRUE);
    }
  }

  /**
   * Download CiviCRM extensions based on configuration in 'extra'.
   *
   * @param bool $cleanExtDir
   *   Flag to decide about cleaning the entire contrib extension dir.
   *
   * @throws \Exception
   */
  public function downloadCivicrmExtensions($cleanExtDir = FALSE) {
    /** @var \Composer\Package\RootPackageInterface $package */
    $package = $this->composer->getPackage();
    $extra = $package->getExtra();
    // No need to sync any file in WordPress.
    if (array_key_exists('cms_type', $extra['civicrm']) &&
      strtolower($extra['civicrm']['cms_type']) == 'wordpress' && !empty($extra['civicrm']['civicrm_wp_plugin_link'])) {
      $civicrm_vendor_dir = getcwd() . '/vendor/civicrm/civicrm-core';
      $civicrm_plugin = getcwd() . '/wp-content/plugins/civicrm/civicrm';
      $this->output("<info>Check CiviCRM plugin soft link {$civicrm_plugin} to {$civicrm_vendor_dir}...</info>");
      if (is_link($civicrm_plugin)) {
        $this->output("<info>Remove link...</info>");
        $this->filesystem->remove($civicrm_plugin);
      }
      try {
        $this->output("<info>Create link...</info>");
        $this->filesystem->symlink($civicrm_vendor_dir, $civicrm_plugin);
      }
      catch (IOException $exception) {
        $this->output("<error>Failed to create soft link for {$civicrm_plugin} to {$civicrm_vendor_dir}...</error>");
      }
      // create packages soft link
      $civicrm_packages_vendor_dir = getcwd() . '/vendor/civicrm/civicrm-packages';
      $civicrm_packages_dir = getcwd() . '/wp-content/plugins/civicrm/civicrm/packages';
      $this->output("<info>Check CiviCRM plugin soft link {$civicrm_packages_dir} to {$civicrm_packages_vendor_dir}...</info>");
      if (is_link($civicrm_packages_dir)) {
        $this->output("<info>Remove link...</info>");
        $this->filesystem->remove($civicrm_packages_dir);
      }
      try {
        $this->output("<info>Create link...</info>");
        $this->filesystem->symlink($civicrm_packages_vendor_dir, $civicrm_packages_dir);
      }
      catch (IOException $exception) {
        $this->output("<error>Failed to create soft link for {$civicrm_packages_dir} to {$civicrm_packages_vendor_dir}...</error>");
      }

    }

    if (!empty($extra['civicrm']['extensions'])) {
      $extensions_dir = $extra['civicrm']['extensions_dir'] ?? '';
      // Get extension dir path from composer file.
      $extensions_install_path = $extra['civicrm']['extensions_install_path'] ?? '';
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
      if ($cleanExtDir) {
        $this->output("<info>Cleaning {$extensions_install_path} directory...</info>");
        // Find out the soft links and unlink it.
        // There is bug in removeDirectoryRecursively which can not unlink
        // the softlink https://github.com/php/php-src/issues/9674
        // This is alternative solution to deal with softlinks.
        $symLinks = glob($extensions_install_path . '/*', GLOB_NOCHECK);
        foreach ($symLinks as $symLink) {
          if (is_link($symLink)) {
            $this->filesystem->remove($symLink);
          }
        }
        $this->util->removeDirectoryRecursively($extensions_install_path, TRUE);
      }
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
        try {
          $this->downloadCivicrmExtension($extensions_install_path, $name, $info['url'], $info['patches'], $extensions_dir_path, $info['link']);
        }
        catch (\Exception $exception) {
          $msg = $exception->getMessage();
          $this->output("<error>{$msg}</error>");
        }
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
    // Remove any old copies of the extension lying around.
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
      $res = $zip->open($extension_archive_file);
      if ($res !== TRUE) {
        $this->output("<error>Unable to Download extension.</error>");
        return;
      }
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
      // The order here is intentional. p1 is most likely to apply with git
      // apply.
      // p0 is next likely. p2 is extremely unlikely, but for some special
      // cases, it might be useful. p4 is useful for Magento 2 patches.
      $patch_levels = ['-p1', '-p0', '-p2', '-p4'];
      foreach ($patches as $patch) {
        $this->output("\t|-> Applying patch: <info>$patch</info>");
        foreach ($patch_levels as $patch_level) {
          // --no-backup-if-mismatch here is a hack that fixes some
          // differences between how patch works on windows and unix.
          if ($patched = $this->executeCommand("patch %s --no-backup-if-mismatch -d %s < %s", $patch_level, $destination_path, $patch)) {
            break;
          }
        }
        if (!$patched) {
          throw new \Exception("Cannot apply patch $patch");
        }
      }
    }
  }

  /**
   * Executes a shell command with escaping.
   *
   * @param string $cmd
   *   Command to execute.
   *
   * @return bool
   *   Check patch applied or not.
   */
  protected function executeCommand($cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    // print_r($command);
    $output = '';
    if ($this->io->isVerbose()) {
      $this->io->write('<comment>' . $command . '</comment>');
      $io = $this->io;
      $output = function ($type, $data) use ($io) {
        if ($type == Process::ERR) {
          $io->write('<error>' . $data . '</error>');
        }
        else {
          $io->write('<comment>' . $data . '</comment>');
        }
      };
    }

    return ($this->executor->execute($command, $output) == 0);
  }

}
