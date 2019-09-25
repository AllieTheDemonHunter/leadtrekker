# leadtrekker
Drupal Leadtrekker Module `1.3.1-dev`

_Makes it easy to setup a Webform with Leadtrekker capability._

This module integrates the Leadtrekker API into Drupal 7 with the following modules:
* Webform
* Rules
* Token
* Field API

## The setup
* Install as per usual Drupal.
* The main target of this module, is extending the Webform module:
  * On each form component, a flag is now available to map it to the correct _required_ Leadtrekker arguments.
    * Another option is available: Source IDs. This involves a large amount of setup to work correctly.
  * The form settings tab has a Source ID field - which is compulsory.
* The admin settings page has the field for specifying the site-wide API key. /admin/config/services/leadtrekker

Visit the official Leadtrekker API page for more info: http://docs.leadtrekker.com/

## Known Issues

**@TODO** The source_id field has to be recognised irrespective of what its field instance's name is. **@FIXED** `1.2.2`

**@FIXED** `1.2.2` Multiple selects/radios which didn't register correctly have been improved.

**@TODO** `1.3.0-dev` superceeds version `1.2.2` as it had been rebuilt to exclude the source_id submodule.

**UPDATE** `1.2.2` has a flaw and upgrading is recommended. Make a backup.
