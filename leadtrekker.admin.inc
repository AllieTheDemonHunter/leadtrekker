<?php

/**
 * Module settings form.
 */
function leadtrekker_admin_settings_form() {
  $form['leadtrekker_api_key'] = [
    '#type' => 'textfield',
    '#title' => t('Leadtrekker API Key'),
    '#multiple' => FALSE,
    '#description' => t('This key will be provided by <a href="http://www.starbright.co.za" title="Get your Leadtrekker subscription.">Starbright</a>.<br>Use <pre>D35069BC33A238B34</pre> when in developer mode.'),
    '#default_value' => variable_get('leadtrekker_api_key', ''),
  ];

  $form['leadtrekker_url'] = [
    '#type' => 'textfield',
    '#title' => t('Leadtrekker Live URL'),
    '#multiple' => FALSE,
    '#description' => t('This value is here for reference only, it <em>cannot be changed</em>.'),
    '#default_value' => "http://my.leadtrekker.co.za/api.php",
    '#attributes' => ['readonly' => 'readonly'],
  ];

  $form['leadtrekker_url_debug'] = [
    '#type' => 'textfield',
    '#title' => t('Leadtrekker Debug URL'),
    '#multiple' => FALSE,
    '#description' => t('This value is here for reference only, it <em>cannot be changed</em>.'),
    '#default_value' => "http://dev-leadtrekker.starbright.co.za/api.php",
    '#attributes' => ['readonly' => 'readonly'],
  ];

  $form['leadtrekker_debug'] = [
    '#type' => 'checkbox',
    '#title' => t('Leadtrekker Debug Mode'),
    '#multiple' => FALSE,
    '#description' => t('Checking this box enables developer mode. All submissions will be sent to a debugging account only.'),
    '#default_value' => variable_get('leadtrekker_debug', 0),
  ];


  $form['leadtrekker_enabled'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable lead processing'),
    '#multiple' => FALSE,
    '#description' => t('Send leads to leadtrekker'),
    '#default_value' => variable_get('leadtrekker_enabled', TRUE),
  ];

  return system_settings_form($form);
}