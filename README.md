# Composer plugin for Drupal projects with CiviCRM

This Composer plugin can be added to a fully 'composerized' Drupal 9 site
in order to easily install CiviCRM on it.

This will ONLY work on a Drupal 9 site based on
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project),
so if you have an older Drupal 9 site that's not, you'll need to convert it
before using this plugin.

## Usage

You need a couple of dependencies first:

- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) (at a relatively recent version)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

*Make sure that you have a recent version of Composer! A couple of people have
tried to use this plugin with older versions and have experienced issues.*

After that, you can run this command:

```
composer require civicrm/civicrm-extension-plugin
```
## Installing CiviCRM
Install CiviCRM using composer, then install this plugin.
