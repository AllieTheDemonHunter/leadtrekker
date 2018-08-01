<?php

/**
 * @module leadtrekker
 * @author Allie du Plooy <allie.rockdev@gmail.com>
 *
 */

const LEADTREKKER_URL_DEBUG = "http://dev-leadtrekker.starbright.co.za/api.php";
const LEADTREKKER_KEY_DEBUG = "KK3UY2WGD96II5GPNSWIG731";

include_once("leadtrekker/lead_trekker.php");

class lead_trekker implements \leadtrekker\lead_trekker
{

    public $lead_id = FALSE;
    public $send_mail = TRUE;
    var $source_id;
    var $cookie_name = "leadtrekker";

    // Typically used to identify which webform Leadtrekker is dealing with.
    protected $leadtrekker_api_key = LEADTREKKER_KEY_DEBUG;
    protected $debug = FALSE;
    private $curl_resource;

    public function __construct($leadtrekker_api_key, $source_id, $lead_data, $send_mail = TRUE, $debug = FALSE)
    {
        $this->debug = $debug;

        if ($leadtrekker_api_key != '') {
            $this->leadtrekker_api_key = $leadtrekker_api_key;
        }

        // Source ids are unique to webform instances.
        if (!$this->debug) {
            $this->source_id = $source_id;
        } else {
            $this->source_id = 1; // Development value.
        }

        $this->query($lead_data);


    return FALSE;
  }

    function query(array $lead_data)
    {
        $this->leadtrekker_inject_fields($lead_data);
        array_walk_recursive($lead_data, "base_it");
        $data = json_encode($lead_data);

        $url = 'https://system.leadtrekker.com/api/createlead';
        $method = 'POST';
        $cSession = curl_init();
        $headers = array();
        $leadtrekker_key = $this->source_id;
        $headers[] = 'api_key:' . $leadtrekker_key;
        curl_setopt($cSession, CURLOPT_URL, $url);
        curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cSession, CURLOPT_HEADER, false);
        curl_setopt($cSession, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($cSession, CURLOPT_POSTFIELDS, $data);
        curl_setopt($cSession, CURLOPT_SSL_VERIFYPEER, false);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($cSession, CURLOPT_HTTPHEADER, $headers);
        curl_exec($cSession);
        curl_close($cSession);
    }

    /**
     * Function which considers $_SESSION data for injection
     * with *any* lead creation.
     */
    function leadtrekker_inject_fields(&$lead_data)
    {
        global $_SESSION;
        $fields_aggregated = [];

        if (isset($lead_data['custom_fields'])) {
            if (is_array($lead_data['custom_fields'])) {
                $fields_aggregated = $lead_data['custom_fields'];
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
            $lead_data["custom_fields"][$key] = $field_data;
        }
    }

    public function __destruct()
    {
        if ($this->curl_resource) {
            curl_close($this->curl_resource);
        }
    }

    function base_it(&$value)
    {
        $value = base64_encode($value);
    }
}
