<?php
/**
 * Created by PhpStorm.
 * User: allie
 * Date: 16/06/03
 * Time: 11:17 AM
 */
namespace leadtrekker;

interface lead_trekker {
  public function __construct($leadtrekker_api_key, $source_id, $lead_data, $send_mail = TRUE, $debug = FALSE);

  public function __destruct();

  public function query();

  /**
   * Function which considers $_SESSION data for injection
   * with *any* lead creation.
   */
  public function leadtrekker_inject_fields();
}