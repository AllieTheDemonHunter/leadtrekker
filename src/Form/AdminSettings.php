<?php

namespace Drupal\leadtrekker\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminSettings.
 *
 * @package Drupal\leadtrekker\Form
 */
class AdminSettings extends FormBase
{

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
    public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, EntityTypeManager $entityTypeManager, NodeStorageInterface $node_storage)
    {
        $this->connection = $connection;
        $this->configFactory = $config_factory->getEditable('leadtrekker.settings');
        $this->entityTypeManager = $entityTypeManager;
        $this->nodeStorage = $node_storage;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
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
    public function getFormId()
    {
        return 'leadtrekker_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = [];


        $form['settings'] = [
            '#title' => $this->t('Access'),
            '#type' => 'details',
        ];

        $form['settings']['leadtrekker_key'] = [
            '#title' => $this->t('Leadtrekker Key'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $this->configFactory->get('leadtrekker_key'),
            '#description' => $this->t('Enter the Leadtrekker access key here.'),
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
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->configFactory->set('leadtrekker_key', $form_state->getValue('leadtrekker_key'))->save();

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
