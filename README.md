# leadtrekker
Drupal Leadtrekker Module

_Makes it easy to setup a Webform with Leadtrekker capability._

This module integrates the Leadtrekker API into Drupal 7 with the following modules:
* Webform
* Rules
* Token
* Field API (Sub module _source_id_)

## The setup
* Install as per usual Drupal.
* The main target of this module, is extending the Webform module:
  * On each form component, a flag is now available to map it to the correct _required_ Leadtrekker arguments.
    * If the _source_id_ sub-module is installed, another option is available: Source IDs.
  * The form settings tab has a Source ID field - which is compulsory.
* The admin settings page has the field for specifying the site-wide API key. /admin/config/services/leadtrekker

Visit the official Leadtrekker API page for more info: http://docs.leadtrekker.com/
