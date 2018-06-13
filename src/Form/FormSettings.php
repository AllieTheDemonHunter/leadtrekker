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
class FormSettings extends FormBase
{

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
    public function __construct(Client $client, EntityTypeManager $entityTypeManager, ModuleHandler $moduleHandler, Connection $database)
    {
        $this->httpClient = $client;
        $this->entityTypeManager = $entityTypeManager;
        $this->moduleHandler = $moduleHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
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
    public function getFormId()
    {
        return 'leadtrekker_form_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = NULL)
    {
        $form = [];

        $nid = $node;
        $form['webform_id'] = [
            '#type' => 'hidden',
            '#value' => $nid,
        ];

        $form['sourceid'] = [
            '#title' => $this->t('Leadtrekker Source ID'),
            '#type' => 'text',
            '#default_value' => "",
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => ('Save Configuration'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

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
