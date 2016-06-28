<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 2016/06/27
 * Time: 11:30 AM
 */

/**
 *  Checks for a pattern identifying Leadtrekker or PMailer.
 */
function _leadtrekker_recognise($url) {
  global $_SESSION;
  $query_array = [];
  parse_str($url, $query_array);

  if (!empty($query_array)) {

    $pattern['google'] = array('campaign', 'adgroup', 'keyword');
    $pattern['mail'] = array('utm_source', 'utm_medium', 'utm_campaign');

    foreach ($pattern as $source_to_check => $required_keys) {
      foreach ($required_keys as $required_key) {
        if (array_key_exists($required_key, $query_array)) {
          $query_array['offsite_campaign_source'] = $source_to_check;
        }
      }
    }
  }

  /**
   * This query set seems legit.
   */
  if (isset($query_array['offsite_campaign_source'])) {
    _leadtrekker_register($query_array);
  }
}

function _leadtrekker_register($external_reference_info) {
  global $_SESSION;
  // We're saving it along with any LT submission.
  // This also means that only one external reference can be used.
  $_SESSION['leadtrekker'] = $external_reference_info;
}

function leadtrekker_boot() {
  global $_SESSION, $_SERVER;

  session_start();

  if (isset($_SESSION['leadtrekker']) && !empty($_SESSION['leadtrekker'])) {
    // This data can now be used globally for any Leadtrekker submissions.
  }
  else {
    //watchdog('Leadtrekker API', "No Global Leadtrekker in session.");
  }

  if ($_SERVER['QUERY_STRING'] != "") {
    _leadtrekker_recognise($_SERVER['QUERY_STRING']);
  }
  else {
    //watchdog('Leadtrekker API', "No query string in URL session found");
  }
}

function watchdog($module, $data) {
  echo "<div class='debug-message'><h3>";
  echo $module;
  echo "</h3>";
  echo "<pre>";
  print_r($data);
  echo "</pre></div>";
}