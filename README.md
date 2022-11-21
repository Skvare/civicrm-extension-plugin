# Composer plugin for Drupal projects with CiviCRM

This Composer plugin can be added to a fully 'composerized' Drupal 9 site
in order to easily install CiviCRM extensions on it.

This will ONLY work on a Drupal 9 site based on
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project),
so if you have an older Drupal 9 site that's not, you'll need to convert it
before using this plugin.

## Usage

You need a couple of dependencies first:

- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) (at a relatively recent version)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- CiviCRM

*Make sure that you have a recent version of Composer!.*

**CiviCRM is required for this plugin.**

Put Skvare repository path in `composer.json` to locate this plugin under
`repositories` section.
```
"civicrm-extension-plugin": {
    "type": "vcs",
    "url":  "ssh://git@git.skvare.com:27271/Core/civicrm-extension-plugin.git"
},
```
Then Run:
```
composer require civicrm/civicrm-extension-plugin
```
**Configuration require for downloading extension through composer.json file.**

Example:

```json
    "extra": {
        "civicrm": {
            "extensions_dir": "/var/www/web/civicrm-extensions/",
            "extensions_install_path": "./web/sites/default/civicrm/extensions/contrib",
            "extensions": {
                "org.wikimedia.relationshipblock": {
                    "link": "org.wikimedia.relationshipblock-1.3",
                    "url": "https://github.com/eileenmcnaughton/org.wikimedia.relationshipblock/archive/1.3.zip"
                },
                "uk.co.vedaconsulting.gdpr": {
                    "link": "uk.co.vedaconsulting.gdpr-2.7",
                    "url": "https://github.com/veda-consulting-company/uk.co.vedaconsulting.gdpr/archive/v2.7.zip"
                },
                "uk.co.vedaconsulting.mosaico": {
                    "link": "uk.co.vedaconsulting.mosaico-2.5",
                    "url": "https://github.com/veda-consulting-company/uk.co.vedaconsulting.mosaico/archive/2.5.zip"
                },
                "org.civicrm.shoreditch": {
                    "link": "org.civicrm.shoreditch-1.0.0-beta.1",
                    "url": "https://github.com/civicrm/org.civicrm.shoreditch/archive/1.0.0-beta.2.zip"
                },
                "org.civicrm.flexmailer": {
                    "link": "org.civicrm.flexmailer-1.1.1",
                    "url": "https://github.com/civicrm/org.civicrm.flexmailer/archive/v1.1.1.zip"
                },
                "org.civicrm.contactlayout": {
                    "link": "org.civicrm.contactlayout-1.7.1",
                    "url": "https://github.com/civicrm/org.civicrm.contactlayout/archive/1.7.1.zip"
                },
                "org.civicrm.angularprofiles": {
                    "link": "org.civicrm.angularprofiles-4.7.31-1.1.2",
                    "url": "https://github.com/ginkgostreet/org.civicrm.angularprofiles/archive/v4.7.31-1.1.2.zip"
                },
                "com.skvare.commonfix": {
                    "link": "com.skvare.commonfix"
                },
                "net.ourpowerbase.sumfields": {
                    "link": "net.ourpowerbase.sumfields",
                    "url": "https://github.com/progressivetech/net.ourpowerbase.sumfields/archive/v4.0.2.zip"
                },
                "org.civicrm.module.cividiscount": {
                    "link": "org.civicrm.module.cividiscount-3.8.1",
                    "url": "https://github.com/civicrm/org.civicrm.module.cividiscount/archive/3.8.1.zip"
                },
                "ca.civicrm.logviewer" : {
                    "url": "https://github.com/adixon/ca.civicrm.logviewer/archive/1.2.zip",
                    "patches": [
                        "./patches/logviewer.patch"
                    ]
                }
            }
        }
     }
```

In composer extra snippet, we have defined default civicrm extension path
`"extensions_dir": "/var/www/web/civicrm-extensions/"`.

For local development, if your common extension directory path is different
then Developer can override this path by creating local_extension.php file
in root directory (file is git ignored).

Example
```php
<?php
$extensions_dir = 'PATH_TO_EXTENSION/civicrm-extensions/'
```
**Now how the link and url get integrated :**

* If link is defined , preference goes to creating soft link, if target extension directory for soft link missing then it download the extension
  using url.
* We can also patch the extension code for downloaded code using url, patch
  does no work with soft link.
* All contrib extension will get downloaded at defined location
 `extensions_install_path` in `composer.json` file, if it is not mentioned then
 default path is `web/sites/default/civicrm/extensions/contrib`.

**Setting up a local fresh**

Extension are linked or downloade when civicrm installed or updated using
composer.

### After installation and configuration:
Run `composer list` command, this will show you
```composer
 civicrm
  civicrm:download-extensions    Download CiviCRM extensions defined in composer.json
  civicrm:publish                Publish web assets from CiviCRM-related projects
```

Run `composer civicrm:download-extensions` command to download extension.

