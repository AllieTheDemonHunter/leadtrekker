<?php

namespace Drupal\leadtrekker\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\ContextProvider\WebformRouteContext;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormSettings.
 *
 * @package Drupal\leadtrekker\Form
 */
class FormSettings extends FormBase
{

    protected $webformRouteContext;

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
    public function __construct(Client $client, EntityTypeManager $entityTypeManager, ModuleHandler $moduleHandler, Connection $database, WebformRouteContext $webformRouteContext)
    {
        $this->httpClient = $client;
        $this->entityTypeManager = $entityTypeManager;
        $this->moduleHandler = $moduleHandler;
        $this->database = $database;
        $this->webformRouteContext = $webformRouteContext;
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
            $container->get('database'),
            $container->get('webform.webform_route_context')
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
        $webform = \Drupal::service("current_route_match")->getParameter("webform");
        $webform_id = $webform->get("id");

        $source_id = $this->database->select('leadtrekker_source_id', 'h')
            ->fields('h', ['source_id'])
            ->condition('id', $webform_id)
            ->execute()->fetchField();

        $form['source_id'] = [
            '#type' => 'textfield',
            '#title' => 'Leadtrekker Source ID',
            '#default_value' => $source_id
        ];

        $form['webform_id'] = [
            '#type' => 'hidden',
            '#title' => 'Webform ID',
            '#default_value' => $webform_id
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
        $this->database->delete('leadtrekker_source_id')->condition('id', $form_state->getValue(['webform_id']))->execute();
        $fields = [
            'id' => $form_state->getValue(['webform_id']),
            'source_id' => $form_state->getValue(['source_id'])
        ];

        try {
            $this->database->insert('leadtrekker_source_id')->fields($fields)->execute();
        } catch (\Exception $e) {

        }

        drupal_set_message($this->t('The configuration options have been saved.'));
    }

    public function getEditableConfigNames()
    {
        // TODO: Implement getEditableConfigNames() method.
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (strlen($form_state->getValue('phone_number')) < 3) {
            // $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
        }
    }
}
