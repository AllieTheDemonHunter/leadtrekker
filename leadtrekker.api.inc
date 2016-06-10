<?php

/**
 * @module leadtrekker
 * @author Allie du Plooy <allie.rockdev@gmail.com>
 *
 */

const CREATE_LEAD = "createlead";
const ADD_FIELD_TO_LEAD = "addcustomfield";
const SEND_MAIL = "notifybyemail";

const LEADTREKKER_URL = "https://my.leadtrekker.co.za/api.php";
const LEADTREKKER_URL_DEBUG = "http://dev-leadtrekker.starbright.co.za/api.php";
const LEADTREKKER_KEY_DEBUG = "D35069BC33A238B34";

include_once("leadtrekker/lead_trekker.php");

class lead_trekker implements \leadtrekker\lead_trekker {

  public $lead_id = FALSE;
  private $curl_resource;
  protected $leadtrekker_api_key = LEADTREKKER_KEY_DEBUG;
  protected $debug = false;

  // Legacy.
  var $send_mail = FALSE;

  // Typically used to identify which webform Leadtrekker is dealing with.
  var $source_id;
  var $cookie_name = "leadtrekker";
  var $leadtrekker_api_url = LEADTREKKER_URL;

  public function __construct($leadtrekker_api_key, $source_id, $lead_data, $debug = false) {
    $this->debug = $debug;
    if($this->debug) {
      $this->leadtrekker_api_url = LEADTREKKER_URL_DEBUG;
    }

    if($leadtrekker_api_key != '') {
      $this->leadtrekker_api_key =  $leadtrekker_api_key;
    }

    // Source ids are unique to webform instances.
    $this->source_id = $source_id;

    $this->curl_resource = $this->_connect(); // Once
    if (!$this->lead_id) {

        if ($this->query(CREATE_LEAD, $lead_data)) {

        //INJECT VARS FROM $_SESSION && #fields if set.
        $this->leadtrekker_inject_fields($lead_data);

        if ($this->send_mail) {
          $this->query(SEND_MAIL);
        }

        return TRUE;
      }
    }

    return FALSE;
  }

  public function __destruct() {
    if ($this->curl_resource) {
      curl_close($this->curl_resource);
    }
  }

  public function _connect() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->leadtrekker_api_url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    //curl_setopt($ch, CURLOPT_UPLOAD, TRUE);
    //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
    /**
     * @value CURLOPT_RETURNTRANSFER (bool) option below;
     * TRUE to return the transfer as a string of the return
     * value of curl_exec() instead of outputting it out directly.
     */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($this->debug) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    } else {
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    return $ch;
  }

  public function query($function, $data = array()) {

    $data["function"] = $function;
    $data['key'] = $this->leadtrekker_api_key;
    $data["sourceid"] = $this->source_id;
    $data["sendemail"] = $this->send_mail;
    if ($this->lead_id) {
      $data["leadid"] = $this->lead_id;
    }

    foreach($data as $key => $value ) {
      if(is_string($value)) {
        $new_array[$key] = urlencode($value);
      } elseif(is_array($value)) {
        foreach ($value as $key2 => $value2) {
          if (is_string($value2)) {
            $new_array[$key][$key2] = urlencode($value2);
          } else {
            $new_array[$key][$key2] = $value2;
          }
        }
      } else {
        $new_array[$key] = $value;
      }
    }

    if ($this->curl_resource) {
      $post_string = $data;
    }
    else {
      return FALSE; // Shouldn't happen.
    }

    @curl_setopt($this->curl_resource, CURLOPT_POSTFIELDS, $post_string);
    watchdog('leadtrekker', "Data to be posted: !data",array('!data' => print_r($post_string,1)));

    // The Leadtrekker Api should return an id.
    if (!$this->lead_id) {
      // Init call
      $this->lead_id = curl_exec($this->curl_resource);
    }
    elseif ($response = curl_exec($this->curl_resource)) {
      watchdog('leadtrekker', "Data returned: !data", array('!data' => print_r($response, 1)));
      if ($response == "OK") {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }


  /**
   * Function which considers $_SESSION data for injection
   * with *any* lead creation.
   */
  public function leadtrekker_inject_fields(&$lead_data) {
    global $_SESSION;
    $fields_aggregated = [];

    if (is_array($lead_data['#fields'])) {
      $fields_aggregated = $lead_data['#fields'];
    }

    if (isset($_SESSION['leadtrekker'])) {
      $custom_field_data = $_SESSION['leadtrekker'];

      if (is_array($custom_field_data) && count($custom_field_data) > 0) {
        $fields_aggregated = array_merge($fields_aggregated, $custom_field_data);
      }
    }

    unset($_SESSION['leadtrekker']);

    foreach ($fields_aggregated as $key => $field_data) {
      $this->query(ADD_FIELD_TO_LEAD, array(
        'fieldname' => $key,
        'fieldvalue' => $field_data
      ));
    }
  }
}