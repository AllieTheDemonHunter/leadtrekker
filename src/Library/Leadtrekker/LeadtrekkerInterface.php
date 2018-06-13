<?php

namespace Drupal\leadtrekker;

/**
 * Provides an interface defining constants for leadtrekker.
 */
interface LeadtrekkerInterface {

    public function __construct($leadtrekker_api_key, $source_id, $lead_data, $send_mail = TRUE, $debug = FALSE);

    public function __destruct();

    public function _connect();

    public function query($function, $data = array());

    /**
     * Function which considers $_SESSION data for injection
     * with *any* lead creation.
     */
    public function leadtrekker_inject_fields(&$lead_data);

}