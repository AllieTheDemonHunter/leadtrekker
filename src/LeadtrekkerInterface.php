<?php

namespace Drupal\leadtrekker;

/**
 * Provides an interface defining constants for leadtrekker.
 */
interface LeadtrekkerInterface {

  /**
   * Leadtrekker Client ID.
   *
   * @var string
   */
  const HUBSPOT_CLIENT_ID = '734f89bf-1b88-11e1-829a-3b413536dd4c';

  /**
   * Leadtrekker Scope.
   *
   * @var string
   */
  const HUBSPOT_SCOPE = 'leads-rw contacts-rw offline';

}
