<?php

/**
 * @module leadtrekker
 * @author Allie du Plooy <allie.rockdev@gmail.com>
 *
 */

const CREATE_LEAD = "createlead";
const ADD_FIELD_TO_LEAD = "addcustomfield";
const SEND_MAIL = "notifybyemail";
const CREATEACCOUNT_ONLY_EMAIL = "createaccount_only_email";
const CREATE_ACCOUNT = CREATEACCOUNT_ONLY_EMAIL; // This is because the createaccount method has been deprecated.

const LEADTREKKER_URL = "https://system.leadtrekker.com/api/createlead";
const LEADTREKKER_URL_DEBUG = "http://dev-leadtrekker.starbright.co.za/api.php";
const LEADTREKKER_KEY_DEBUG = "KK3UY2WGD96II5GPNSWIG731";

include_once("leadtrekker/lead_trekker.php");

class lead_trekker
{

  public $lead_id = FALSE;
  public $send_mail = TRUE;
  var $sourceid;
  var $cookie_name = "leadtrekker";
  var $leadtrekker_api_url = LEADTREKKER_URL;

  // Typically used to identify which webform Leadtrekker is dealing with.
  protected $leadtrekker_api_key = LEADTREKKER_KEY_DEBUG;
  protected $debug = FALSE;
  protected $data;

  public function __construct($leadtrekker_api_key, $source_id, $lead_data, $send_mail = TRUE, $debug = FALSE)
  {
    $this->debug = $debug;
    $this->send_mail = $send_mail;
    $this->data = $lead_data;

    if ($this->debug) {
      $this->leadtrekker_api_url = LEADTREKKER_URL_DEBUG;
    }

    if ($leadtrekker_api_key != '') {
      $this->leadtrekker_api_key = $leadtrekker_api_key;
    }

    // Source ids are unique to webform instances.
    if (!$this->debug || !$source_id == CREATEACCOUNT_ONLY_EMAIL) {
      $this->data['sourceid'] = $source_id;
    } else {
      $this->data['sourceid'] = 12345; // Development value.
    }

    $this->leadtrekker_inject_fields();
    $this->query();
  }

  protected function translate_field_names($original)
  {
    $translation_table = [

      'leadname' => 'name',
      'leademail' => 'email',
      'leadnumber' => 'number',
      'custom_field' => 'custom_field',
      'company' => 'company',
      'source_ids' => 'source_ids'
    ];

    foreach ($original as $original_key => $value) {
      if (array_key_exists("$original_key", $translation_table)) {
        $new[$translation_table[$original_key]] = $value;
      } else {
        $new[$original_key] = $value;
      }
    }

    return $new;
  }

  public function query()
  {

    $encode_me = function (&$some_value) {
      $some_value = base64_encode($some_value);
    };

    array_walk_recursive($this->data, $encode_me);

    $this->data = $this->translate_field_names($this->data);

    $data = json_encode($this->data);

    $url = 'https://system.leadtrekker.com/api/createlead';
    $method = 'POST';
    $cSession = curl_init();
    $headers = array();
    $headers[] = 'api_key:' . $this->leadtrekker_api_key;
    curl_setopt($cSession, CURLOPT_URL, $url);
    curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cSession, CURLOPT_HEADER, false);
    curl_setopt($cSession, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($cSession, CURLOPT_POSTFIELDS, $data);
    curl_setopt($cSession, CURLOPT_SSL_VERIFYPEER, false);
    $headers[] = 'Content-Type: application/json';
    curl_setopt($cSession, CURLOPT_HTTPHEADER, $headers);
    $this->lead_id = curl_exec($cSession);
    curl_close($cSession);

    // Checking for problems.
    $lead_id = json_decode($this->lead_id, false);
    if (!is_numeric($lead_id->id)) {
      $this->lead_id = $lead_id->id; // Could contain an error message.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Function which considers $_SESSION data for injection
   * with *any* lead creation.
   */
  public function leadtrekker_inject_fields()
  {
    global $_SESSION;
    $fields_aggregated = [];

    if (isset($this->data['custom_fields'])) {
      if (is_array($this->data['custom_fields'])) {
        $fields_aggregated = $this->data['custom_fields'];
      }
    }

    if (isset($_SESSION['leadtrekker'])) {
      $custom_field_data = $_SESSION['leadtrekker'];

      if (is_array($custom_field_data) && count($custom_field_data) > 0) {
        $fields_aggregated = array_merge($fields_aggregated, $custom_field_data);
      }
    }

    unset($_SESSION['leadtrekker']);

    foreach ($fields_aggregated as $key => $field_data) {
      //This overrides/re-sets existing custom fields.
      $this->data['custom_fields'][$key] = $field_data;
    }
  }
}
