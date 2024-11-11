# Composer plugin for Drupal projects with CiviCRM

This composer plugin can be added to a fully 'composerized' Drupal 9 / 10 / 11 site. In order to easily install CiviCRM extensions on it.

This work on a Drupal 9, 10, 11 site based on
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project),
So if you have an older Drupal 9 site, you'll need to convert it before using this plugin.

## Usage

You need a couple of dependencies first:

- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) (at a relatively recent version)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- CiviCRM

**CiviCRM is required for this plugin.**

Put Skvare repository path in `composer.json` to locate this plugin under
`repositories` section.
```
"civicrm-extension-plugin": {
    "type": "vcs",
    "url":  "https://github.com/Skvare/civicrm-extension-plugin"
},
```
Then Run:
```
composer require civicrm/civicrm-extension-plugin
```
**Configuration require for downloading extensions through the composer.json file:**

Example:

```json
    "extra": {
        "civicrm": {
            "extensions_install_path": "./web/sites/default/civicrm/extensions/contrib",
            "extensions": {
                "org.wikimedia.relationshipblock": {
                    "url": "https://github.com/eileenmcnaughton/org.wikimedia.relationshipblock/archive/1.3.zip"
                },
                "uk.co.vedaconsulting.gdpr": {
                    "url": "https://github.com/veda-consulting-company/uk.co.vedaconsulting.gdpr/archive/v2.7.zip"
                },
                "uk.co.vedaconsulting.mosaico": {
                    "url": "https://github.com/veda-consulting-company/uk.co.vedaconsulting.mosaico/archive/2.5.zip"
                },
                "org.civicrm.shoreditch": {
                    "url": "https://github.com/civicrm/org.civicrm.shoreditch/archive/1.0.0-beta.2.zip"
                },
                "org.civicrm.contactlayout": {
                    "url": "https://github.com/civicrm/org.civicrm.contactlayout/archive/1.7.1.zip"
                },
                "org.civicrm.angularprofiles": {
                    "url": "https://github.com/ginkgostreet/org.civicrm.angularprofiles/archive/v4.7.31-1.1.2.zip"
                },
                "net.ourpowerbase.sumfields": {
                    "url": "https://github.com/progressivetech/net.ourpowerbase.sumfields/archive/v4.0.2.zip"
                },
                "org.civicrm.module.cividiscount": {
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

In the composer extra snippet, we can define civicrm extension path.

The default path is 'web/sites/default/civicrm/extensions/contrib`.
```composer
      "civicrm": {
            "extensions_install_path": "./web/sites/default/civicrm/extensions/contrib",
            "extensions": {
             .
             .
             .
            }
```

You can git ignore the contrib extension path like this in `.gitignore` file
```bash
/web/sites/*/civicrm/extensions/contrib/*
!web/sites/*/civicrm/extensions/contrib/.gitkeep
```
`.gitkeep` is placeholder empty file.
You can keep your custom extension under `web/sites/*/civicrm/extensions/custom/` directory wih GIT control.

**How it works:**

* We can patch the extension code for downloaded code; you can keep all the patches in the `patches` directory.
* All contrib extensions will get downloaded at the defined location.
  `extensions_install_path` in `composer.json` file, if it is not mentioned then
  default path is `web/sites/default/civicrm/extensions/contrib`.
* Extension also downloaded/refreshed when civicrm core installed or updated action happened through composer.

### After installation and configuration:
Run `composer list` command, this will show you
```composer
 civicrm
  civicrm:download-extensions    Download CiviCRM extensions defined in composer.json
  civicrm:publish                Publish web assets from CiviCRM-related projects
```

Run `composer civicrm:download-extensions` command to download extension.

Use `-c, --clean` for clean extension directory before downloading extensions.
Example:
```
Run `composer civicrm:download-extensions -c` command to remove all extension from contrib before download extension.
```
Output:
```composer
composer civicrm:download-extensions -c
> [civicrm-extension-plugin] Cleaning ./web/sites/default/civicrm/extensions/contrib directory...
> [civicrm-extension-plugin] Downloading CiviCRM extension org.wikimedia.relationshipblock from https://github.com/eileenmcnaughton/org.wikimedia.relationshipblock/archive/1.3.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension uk.co.vedaconsulting.gdpr from https://github.com/veda-consulting-company/uk.co.vedaconsulting.gdpr/archive/v2.7.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension uk.co.vedaconsulting.mosaico from https://github.com/veda-consulting-company/uk.co.vedaconsulting.mosaico/archive/2.5.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension org.civicrm.shoreditch from https://github.com/civicrm/org.civicrm.shoreditch/archive/1.0.0-beta.2.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension org.civicrm.contactlayout from https://github.com/civicrm/org.civicrm.contactlayout/archive/1.7.1.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension org.civicrm.angularprofiles from https://github.com/ginkgostreet/org.civicrm.angularprofiles/archive/v4.7.31-1.1.2.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension net.ourpowerbase.sumfields from https://github.com/progressivetech/net.ourpowerbase.sumfields/archive/v4.0.2.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension org.civicrm.module.cividiscount from https://github.com/civicrm/org.civicrm.module.cividiscount/archive/3.8.1.zip...
> [civicrm-extension-plugin] Downloading CiviCRM extension ca.civicrm.logviewer from https://github.com/adixon/ca.civicrm.logviewer/archive/1.2.zip...
> [civicrm-extension-plugin] 	|-> Applying patch: ./patches/logviewer.patch
> [civicrm-extension-plugin] Syncing CiviCRM web assets to /web/libraries/civicrm...
```


This also syn civicrm web assets like:
* kcfinder
* extension-compatibility.json
* ck-options.json

