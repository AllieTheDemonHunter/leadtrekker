<?php

/**
* Implements hook_rules_action_info()
*/
function leadtrekker_rules_action_info(){
  return array(
    'leadtrekker_rules_action_post_lead' => array(
      'label' => t('Create new lead'),
      'group' => t('Leadtrekker'),
      'parameter' => array(
          'notifyEmail' => array(
            'type' => 'boolean',
            'label' => t('Send email'),
            'description' => t('Send the account owner notifications whenever a lead has been created.'),
          ),     
          'source' => array(
            'type' => 'text',
            'label' => t('Source'),
            'description' => t('Identifies this form in Leadtrekker, eg. 3041'),
          ),          
          'leadname' => array(
            'type' => 'text',
            'label' => t('Name'),
          ),
          'leadmail' => array(
            'type' => 'text',
            'label' => t('Email'),
          ),
          'leadnumber' => array(
            'type' => 'text',
            'label' => t('Lead Number'),
          ),
          'customfield' => array(
            'type' => 'list<text>',
            'label' => t('Custom Fields'),
            'decription' => 'A list of key|value pairs, one pair on each line.'
          ),
        ),
    ),
  );
}

/**
* Leadtrekker post lead rules callback
*/
function leadtrekker_rules_action_post_lead($notifyByEmail = false, $source_id, $leadName, $leadEmail, $leadNumber, $customValues){

  $data = [
    'leadname' => $leadName,
    'leademail' => $leadEmail,
    'leadnumber' => $leadNumber,
  ];

  foreach ($customValues as $item) {
    $_item = explode('|', $item);
    $data['#fields'][$_item[0]] = $_item[1];
  }

  send_lead($source_id, $data, $notifyByEmail);
}