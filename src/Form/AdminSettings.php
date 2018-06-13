<?php

namespace Drupal\leadtrekker\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\leadtrekker\LeadtrekkerInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AdminSettings.
 *
 * @package Drupal\leadtrekker\Form
 */
class AdminSettings extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * AdminSettings constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, EntityTypeManager $entityTypeManager, NodeStorageInterface $node_storage) {
    $this->connection = $connection;
    $this->configFactory = $config_factory->getEditable('leadtrekker.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leadtrekker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['additional_settings'] = ['#type' => 'vertical_tabs'];

    $form['settings'] = [
      '#title' => $this->t('Connectivity'),
      '#type' => 'details',
      '#group' => 'additional_settings',
    ];

    $form['settings']['leadtrekker_portalid'] = [
      '#title' => $this->t('Leadtrekker Portal ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configFactory->get('leadtrekker_portalid'),
      '#description' => $this->t('Enter the Leadtrekker Portal ID for this site.  It can be found by
      <a href="https://login.leadtrekker.com/login" target="_blank">logging into Leadtrekker</a> going to the Dashboard and
      examining the url. Example: "https://app.leadtrekker.com/dashboard-plus/12345/dash/".  The number after
      "dashboard-plus" is your Portal ID.'),
    ];

    if ($this->configFactory->get('leadtrekker_portalid')) {
      $form['settings']['leadtrekker_authentication'] = [
        '#value' => $this->t('Connect Leadtrekker Account'),
        '#type' => 'submit',
        '#submit' => [[$this, 'leadtrekkerOauthSubmitForm']],
      ];

      if ($this->configFactory->get('leadtrekker_refresh_token')) {
        $form['settings']['leadtrekker_authentication']['#suffix'] = $this->t('Your Leadtrekker account is connected.');
        $form['settings']['leadtrekker_authentication']['#value'] = $this->t('Disconnect Leadtrekker Account');
        $form['settings']['leadtrekker_authentication']['#submit'] = [[$this, 'leadtrekkerOauthDisconnect']];
      }
    }

    $form['settings']['leadtrekker_log_code'] = [
      '#title' => $this->t('Leadtrekker Traffic Logging Code'),
      '#type' => 'textarea',
      '#default_value' => $this->configFactory->get('leadtrekker_log_code'),
      '#description' => $this->t('To enable Leadtrekker traffic logging on your site, paste the External Site Traffic Logging code
      here.'),
    ];

    // Debugging section.
    $form['debug'] = [
      '#title' => $this->t('Debugging'),
      '#type' => 'details',
      '#group' => 'additional_settings',
    ];

    $form['debug']['leadtrekker_debug_on'] = [
      '#title' => $this->t('Debugging enabled'),
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory->get('leadtrekker_debug_on'),
      '#description' => $this->t('If debugging is enabled, Leadtrekker errors will be emailed to the address below. Otherwise, they
      will be logged to the regular Drupal error log.'),
    ];

    $form['debug']['leadtrekker_debug_email'] = [
      '#title' => $this->t('Debugging email'),
      '#type' => 'email',
      '#default_value' => $this->configFactory->get('leadtrekker_debug_email'),
      '#description' => $this->t('Email error reports to this address if debugging is enabled.'),
    ];

    // Mapping leadtrekker and webform/ webform node forms.
    $leadtrekker_form_description = '';
    $leadtrekker_forms = _leadtrekker_get_forms();
    $leadtrekker_field_options = [];
    $leadtrekker_form_options = '';
    if (isset($leadtrekker_forms['error'])) {
      $leadtrekker_form_description = $leadtrekker_forms['error'];
    }
    else {
      if (empty($leadtrekker_forms['value'])) {
        $leadtrekker_form_description = $this->t('No Leadtrekker forms found. You will need to create a form on Leadtrekker before you can configure it here.');
      }
      else {
        $leadtrekker_form_options = ["--donotmap--" => "Do Not Map"];
        foreach ($leadtrekker_forms['value'] as $leadtrekker_form) {
          $leadtrekker_form_options[$leadtrekker_form['guid']] = $leadtrekker_form['name'];
          $leadtrekker_field_options[$leadtrekker_form['guid']]['fields']['--donotmap--'] = "Do Not Map";
          foreach ($leadtrekker_form['fields'] as $leadtrekker_field) {
            $leadtrekker_field_options[$leadtrekker_form['guid']]['fields'][$leadtrekker_field['name']] = $leadtrekker_field['label'] . ' (' . $leadtrekker_field['fieldType'] . ')';
          }
        }
      }
    }

    $form['webforms'] = [
      '#title' => $this->t('Webforms'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#description' => $this->t('The following webforms have been detected
       and can be configured to submit to the Leadtrekker API.'),
      '#tree' => TRUE,
    ];

    $form['webforms']['#description'] = $leadtrekker_form_description;

    if (!isset($leadtrekker_forms['error'])) {
      if (!empty($leadtrekker_forms['value'])) {

        $webform_types = \Drupal::service('entity.manager')->getStorage('webform')->loadMultiple();

        foreach ($webform_types as $webform_type) {
          $webform_type_id = $webform_type->id();
          $webform_type_label = $webform_type->label();
          $form['webforms'][$webform_type_id] = [
            '#title' => $webform_type_label,
            '#type' => 'details',
          ];
          $form['webforms'][$webform_type_id]['leadtrekker_form'] = [
            '#title' => $this->t('Leadtrekker form'),
            '#type' => 'select',
            '#options' => $leadtrekker_form_options,
            '#default_value' => _leadtrekker_default_value($webform_type_id),
          ];

          foreach ($leadtrekker_form_options as $key => $value) {
            if ($key != '--donotmap--') {
              $form['webforms'][$webform_type_id][$key] = [
                '#title' => $this->t('Field mappings for @field', [
                  '@field' => $value,
                ]),
                '#type' => 'details',
                '#states' => [
                  'visible' => [
                    ':input[name="webforms[' . $webform_type_id . '][leadtrekker_form]"]' => [
                      'value' => $key,
                    ],
                  ],
                ],
              ];

              $webform = $this->entityTypeManager->getStorage('webform')->load($webform_type_id);
              $webform = $webform->getElementsDecoded();

              foreach ($webform as $form_key => $component) {
                if ($component['#type'] !== 'markup') {
                  $form['webforms'][$webform_type_id][$key][$form_key] = [
                    '#title' => $component['#title'] . ' (' . $component['#type'] . ')',
                    '#type' => 'select',
                    '#options' => $leadtrekker_field_options[$key]['fields'],
                    '#default_value' => _leadtrekker_default_value($webform_type_id, $key, $form_key),
                  ];
                }
              }
            }
          }
        }
      }
    }

    // Webform node forms mapping.
    $form['webform_nodes'] = [
      '#title' => $this->t('Webform Nodes'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#description' => $this->t('The following webform Nodes have been detected
       and can be configured to submit to the Leadtrekker API.(Note: Webform Nodes module needs to enabled, to create node webforms.)'),
      '#tree' => TRUE,
    ];
    $form['webform_nodes']['#description'] = $leadtrekker_form_description;

    if (!isset($leadtrekker_forms['error'])) {
      if (!empty($leadtrekker_forms['value'])) {

        $nodes = $this->connection->select('node', 'n')
          ->fields('n', ['nid'])
          ->condition('type', 'webform')
          ->execute()->fetchAll();

        foreach ($nodes as $node) {
          $nid = $node->nid;
          $form['webform_nodes']['nid-' . $nid] = [
            '#title' => $this->nodeStorage->load($nid)->getTitle(),
            '#type' => 'details',
          ];

          $form['webform_nodes']['nid-' . $nid]['leadtrekker_form'] = [
            '#title' => $this->t('Leadtrekker form'),
            '#type' => 'select',
            '#options' => $leadtrekker_form_options,
            '#default_value' => _leadtrekker_default_value($nid),
          ];

          foreach ($leadtrekker_form_options as $key => $value) {
            if ($key != '--donotmap--') {
              $form['webform_nodes']['nid-' . $nid][$key] = [
                '#title' => $this->t('Field mappings for @field', [
                  '@field' => $value,
                ]),
                '#type' => 'details',
                '#states' => [
                  'visible' => [
                    ':input[name="webform_nodes[nid-' . $nid . '][leadtrekker_form]"]' => [
                      'value' => $key,
                    ],
                  ],
                ],
              ];

              $node = $this->nodeStorage->load($nid);
              $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
              $webform_id = $node->$webform_field_name->target_id;

              $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
              $webform = $webform->getElementsDecoded();

              foreach ($webform as $form_key => $component) {
                if ($component['#type'] !== 'markup') {
                  $form['webform_nodes']['nid-' . $nid][$key][$form_key] = [
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
    }

    // Tracking code vertical tab.
    $form['tracking_code'] = [
      '#title' => $this->t('Tracking Code'),
      '#type' => 'details',
      '#group' => 'additional_settings',
    ];

    $form['tracking_code']['tracking_code_on'] = [
      '#title' => $this->t('Enable Tracking Code'),
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory->get('tracking_code_on'),
      '#description' => $this->t('If Tracking code is enabled, Javascript
      tracking will be inserted in all/specified pages of the site as configured
       in leadtrekker account.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => ('Save Configuration'),
    ];

    return $form;
  }

  /**
   * Submit handler of admin config form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->set('leadtrekker_portalid', $form_state->getValue('leadtrekker_portalid'))
      ->set('leadtrekker_debug_email', $form_state->getValue('leadtrekker_debug_email'))
      ->set('leadtrekker_debug_on', $form_state->getValue('leadtrekker_debug_on'))
      ->set('leadtrekker_log_code', $form_state->getValue(['leadtrekker_log_code']))
      ->set('tracking_code_on', $form_state->getValue(['tracking_code_on']))
      ->save();

    // Check if webform values even exist before continuing.
    if (!$form_state->getValue('webforms')) {

      foreach ($form_state->getValue('webforms') as $key => $settings) {
        $this->connection->delete('leadtrekker')->condition('id', $key)->execute();

        if ($settings['leadtrekker_form'] != '--donotmap--') {
          foreach ($settings[$settings['leadtrekker_form']] as $webform_field => $leadtrekker_field) {
            $fields = [
              'nid' => $key,
              'leadtrekker_guid' => $settings['leadtrekker_form'],
              'webform_field' => $webform_field,
              'leadtrekker_field' => $leadtrekker_field,
            ];
            $this->connection->insert('leadtrekker')->fields($fields)->execute();
          }
        }
      }
    }
    else {
      // Insert entry.
      foreach ($form_state->getValue('webforms') as $key => $settings) {
        $this->connection->delete('leadtrekker')->condition('id', $key)->execute();
        if ($settings['leadtrekker_form'] != '--donotmap--') {
          foreach ($settings[$settings['leadtrekker_form']] as $webform_field => $leadtrekker_field) {
            $fields = [
              'id' => $key,
              'leadtrekker_guid' => $settings['leadtrekker_form'],
              'webform_field' => $webform_field,
              'leadtrekker_field' => $leadtrekker_field,
            ];
            $this->connection->insert('leadtrekker')->fields($fields)->execute();
          }
        }
      }
    }

    // Check if webform values even exist before continuing.
    if (!$form_state->getValue('webform_nodes')) {

      foreach ($form_state->getValue('webform_nodes') as $key => $settings) {
        $this->connection->delete('leadtrekker')->condition('id', str_replace('nid-', '', $key))->execute();

        if ($settings['leadtrekker_form'] != '--donotmap--') {
          foreach ($settings[$settings['leadtrekker_form']] as $webform_field => $leadtrekker_field) {
            $fields = [
              'id' => str_replace('nid-', '', $key),
              'leadtrekker_guid' => $settings['leadtrekker_form'],
              'webform_field' => $webform_field,
              'leadtrekker_field' => $leadtrekker_field,
            ];
            $this->connection->insert('leadtrekker')->fields($fields)->execute();
          }
        }
      }
    }
    else {
      // Insert entry.
      foreach ($form_state->getValue('webform_nodes') as $key => $settings) {
        $this->connection->delete('leadtrekker')->condition('id', str_replace('nid-', '', $key))->execute();
        if ($settings['leadtrekker_form'] != '--donotmap--') {
          foreach ($settings[$settings['leadtrekker_form']] as $webform_field => $leadtrekker_field) {
            $fields = [
              'id' => str_replace('nid-', '', $key),
              'leadtrekker_guid' => $settings['leadtrekker_form'],
              'webform_field' => $webform_field,
              'leadtrekker_field' => $leadtrekker_field,
            ];
            $this->connection->insert('leadtrekker')->fields($fields)->execute();
          }
        }
      }
    }

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Form submission handler for leadtrekker_admin_settings().
   */
  public function leadtrekkerOauthSubmitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $options = [
      'query' => [
        'client_id' => LeadtrekkerInterface::HUBSPOT_CLIENT_ID,
        'portalId' => $this->configFactory->get('leadtrekker_portalid'),
        'redirect_uri' => $base_url . Url::fromRoute('leadtrekker.oauth_connect')->toString(),
        'scope' => LeadtrekkerInterface::HUBSPOT_SCOPE,
      ],
    ];
    $redirect_url = Url::fromUri('https://app.leadtrekker.com/auth/authenticate', $options)->toString();

    $response = new RedirectResponse($redirect_url);
    $response->send();
    return $response;
  }

  /**
   * Form submit handler.
   *
   * Deletes Leadtrekker OAuth tokens.
   */
  public function leadtrekkerOauthDisconnect(array &$form, FormStateInterface $form_state) {
    $this->configFactory->clear('leadtrekker_refresh_token')->save();
  }

}
