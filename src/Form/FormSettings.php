<?php

namespace Drupal\leadtrekker\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormSettings.
 *
 * @package Drupal\leadtrekker\Form
 */
class FormSettings extends FormBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * FormSettings constructor.
   */
  public function __construct(Client $client, EntityTypeManager $entityTypeManager, ModuleHandler $moduleHandler, Connection $database) {
    $this->httpClient = $client;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->database = $this->database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leadtrekker_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form = [];

    $leadtrekker_forms = _leadtrekker_get_forms();

    if (isset($leadtrekker_forms['error'])) {
      $form['webforms']['#description'] = $leadtrekker_forms['error'];
    }
    else {
      if (empty($leadtrekker_forms['value'])) {
        $form['webforms']['#description'] = $this->t('No Leadtrekker forms found. You will need to create a form on Leadtrekker before you can configure it here.');
      }
      else {
        $leadtrekker_form_options = ["--donotmap--" => "Do Not Map"];
        $leadtrekker_field_options = [];
        foreach ($leadtrekker_forms['value'] as $leadtrekker_form) {
          $leadtrekker_form_options[$leadtrekker_form['guid']] = $leadtrekker_form['name'];
          $leadtrekker_field_options[$leadtrekker_form['guid']]['fields']['--donotmap--'] = "Do Not Map";

          foreach ($leadtrekker_form['fields'] as $leadtrekker_field) {
            $leadtrekker_field_options[$leadtrekker_form['guid']]['fields'][$leadtrekker_field['name']] = ($leadtrekker_field['label'] ? $leadtrekker_field['label'] : $leadtrekker_field['name']) . ' (' . $leadtrekker_field['fieldType'] . ')';
          }
        }

        $nid = $node;
        $form['nid'] = [
          '#type' => 'hidden',
          '#value' => $nid,
        ];

        $form['leadtrekker_form'] = [
          '#title' => $this->t('Leadtrekker form'),
          '#type' => 'select',
          '#options' => $leadtrekker_form_options,
          '#default_value' => _leadtrekker_default_value($nid),
        ];

        foreach ($leadtrekker_form_options as $key => $value) {
          if ($key != '--donotmap--') {
            $form[$key] = [
              '#title' => $this->t('Field mappings for @field', [
                '@field' => $value,
              ]),
              '#type' => 'details',
              '#tree' => TRUE,
              '#states' => [
                'visible' => [
                  ':input[name="leadtrekker_form"]' => [
                    'value' => $key,
                  ],
                ],
              ],
            ];

            $webform = $this->entityTypeManager->getStorage('webform')->load('test_1');
            $webform = $webform->getElementsDecoded();

            foreach ($webform as $form_key => $component) {
              if ($component['#type'] !== 'markup') {
                $form[$key][$form_key] = [
                  '#title' => $component['#title'] . ' (' . $component['#type'] . ')',
                  '#type' => 'select',
                  '#options' => $leadtrekker_field_options[$key]['fields'],
                  '#default_value' => _leadtrekker_default_value($nid, $key, $form_key),
                ];
              }
            }
          }
        }
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => ('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->database->delete('leadtrekker')->condition('id', $form_state->getValue(['nid']))->execute();

    if ($form_state->getValue(['leadtrekker_form']) != '--donotmap--') {
      foreach ($form_state->getValue([$form_state->getValue('leadtrekker_form')]) as $webform_field => $leadtrekker_field) {
        $fields = [
          'id' => $form_state->getValue(['nid']),
          'leadtrekker_guid' => $form_state->getValue(['leadtrekker_form']),
          'webform_field' => $webform_field,
          'leadtrekker_field' => $leadtrekker_field,
        ];
        $this->database->insert('leadtrekker')->fields($fields)->execute();
      }
    }
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
