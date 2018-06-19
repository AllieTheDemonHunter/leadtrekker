<?php

namespace Drupal\leadtrekker\Plugin\WebformHandler;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Url;
use Drupal\node\NodeStorageInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote post handler.
 *
 * @LeadtrekkerWebformHandler(
 *   id = "leadtrekker_webform_handler",
 *   label = @Translation("Leadtrekker Webform Handler"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a Leadtrekker form."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class LeadtrekkerWebformHandler extends WebformHandlerBase
{

    /**
     * The HTTP client to fetch the feed data with.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * The node storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $nodeStorage;

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
     * The logger factory.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected $loggerFactory;

    /**
     * The mail manager.
     *
     * @var \Drupal\Core\Mail\MailManagerInterface
     */
    protected $mailManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, ClientInterface $httpClient, NodeStorageInterface $node_storage, Connection $connection, ConfigFactoryInterface $config_factory, LoggerChannelFactory $loggerChannelFactory, MailManager $mailManager)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $loggerChannelFactory, $config_factory, $entity_type_manager, $this->conditionsValidator);
        $this->httpClient = $httpClient;
        $this->nodeStorage = $node_storage;
        $this->connection = $connection;
        $this->configFactory = $config_factory->getEditable('leadtrekker.settings');
        $this->loggerFactory = $loggerChannelFactory;
        $this->mailManager = $mailManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('logger.factory')->get('webform.remote_post'),
            $container->get('entity_type.manager'),
            $container->get('http_client'),
            $container->get('entity.manager')->getStorage('node'),
            $container->get('database'),
            $container->get('config.factory'),
            $container->get('logger.factory'),
            $container->get('plugin.manager.mail')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
    {
        $operation = ($update) ? 'update' : 'insert';
        $this->remotePost($operation, $webform_submission);
    }

    /**
     * Execute a remote post.
     *
     * @param string $operation
     *   The type of webform submission operation to be posted. Can be 'insert',
     *   'update', or 'delete'.
     * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
     *   The webform submission to be posted.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function remotePost($operation, WebformSubmissionInterface $webform_submission)
    {
        $request_post_data = $this->getPostData($operation, $webform_submission);
        $entity_type = $request_post_data['entity_type'];
        if ($entity_type == 'node') {
            // Case 1: of node forms.
            $entity_id = $request_post_data['entity_id'];

            $node = $this->nodeStorage->load($entity_id);

            // Node form title of some webform type.
            $form_title = $node->getTitle();
            // Node id i.e entity id is mapped with leadtrekker form id in leadtrekker table.
            $id = $entity_id;

            $page_url = Url::fromUserInput($request_post_data['uri'], ['absolute' => TRUE])->toString();
        } else {
            // Case 2: Webform it self.
            // Webform id is mapped with leadtrekker form id in leadtrekker table.
            $id = $this->getWebform()->getOriginalId();

            // Webform title.
            $form_title = $this->getWebform()->get('title');
            $page_url = Url::fromUserInput('/form/' . $id, ['absolute' => TRUE])->toString();
        }
        $sourceid = $this->connection->select('leadtrekker', 'h')
            ->fields('h', ['source_id'])
            ->condition('webform_id', $id)
            ->execute()->fetchField();


        $api = "https://system.leadtrekker.com/api/createlead";
        $url = Url::fromUri($api)->toString();

        $data['name'] = base64_encode('John Doe');
        $data['email'] = base64_encode('john.doe@johnsbakery.com');
        $data['number'] = base64_encode('0120040509');
        $data['sourceid'] = base64_encode($sourceid);
        $data['company'] = base64_encode('Johns Bakery');

        $data['custom_fields'] = array(
            'Webform' => base64_encode($form_title),
            'Webform URL' => base64_encode($page_url),
        );

        $data = json_encode($data);

        try {

            $request_options = [
                RequestOptions::HEADERS => ['api_key' => 'D35069BC33A238B34'],
                RequestOptions::JSON => $data
            ];

            $response = $this->httpClient->request('POST', $url, $request_options);
            $data = (string)$response->getBody();

            if ($response->getStatusCode() == '204') {
                $this->loggerFactory->get('leadtrekker')->notice('Webform "%form" results successfully submitted to Leadtrekker. Response: @msg', [
                        '@msg' => strip_tags($data),
                        '%form' => $form_title,
                    ]
                );
            } elseif (!empty($response['Error'])) {
                $this->loggerFactory->get('leadtrekker')->notice('HTTP error when submitting Leadtrekker data from Webform "%form": @error', [
                        '@error' => $response['Error'],
                        '%form' => $form_title,
                    ]
                );

            } else {
                $this->loggerFactory->get('leadtrekker')->notice('Leadtrekker error when submitting Webform "%form": @error', [
                        '@error' => $data,
                        '%form' => $form_title,
                    ]
                );
            }

        } catch (RequestException $e) {
            watchdog_exception('Leadtrekker', $e);
        }

    }

    /**
     * Get a webform submission's post data.
     *
     * @param string $operation
     *   The type of webform submission operation to be posted. Can be 'insert',
     *   'update', or 'delete'.
     * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
     *   The webform submission to be posted.
     *
     * @return array
     *   A webform submission converted to an associative array.
     */
    protected function getPostData($operation, WebformSubmissionInterface $webform_submission)
    {
        // Get submission and elements data.
        $data = $webform_submission->toArray(TRUE);

        // Flatten data.
        // Prioritizing elements before the submissions fields.
        $data = $data['data'] + $data;
        unset($data['data']);

        return $data;
    }

}
