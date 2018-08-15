<?php

namespace Drupal\ethereum_smartcontract\Plugin\rest\resource;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ethereum_smartcontract\Entity\SmartContract as SmartContractEntity;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Ethereum\DataType\EthD32;
use Ethereum\DataType\FilterChange;
use Ethereum\EthereumStatic;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "event_processor",
 *   label = @Translation("Event processor"),
 *   uri_paths = {
 *     "canonical" = "/ethereum/process_tx/{tx_hash}"
 *   }
 * )
 */
class EventProcessor extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EventProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('ethereum_smartcontract'),
      $container->get('current_user')
    );
  }


  /**
   * @param \Ethereum\DataType\FilterChange $filterChange
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   *
   * @return \Ethereum\EmittedEvent[]
   */
  private static function processContractLogs(FilterChange $filterChange) {
    $emittedEvents = [];
    $contractStorage = \Drupal::entityTypeManager()
      ->getStorage('smartcontract');
    $query = $contractStorage->getQuery()->condition('status', 1);
    $entity_ids = $query->execute();

    if (!empty($entity_ids)) {
      // Process TX with enabled contracts.
      foreach ($contractStorage->loadMultiple($entity_ids) as $contract) {
        /* @var $contract SmartContractEntity */
        $event = $contract->getCallable()->processLog($filterChange);
        if ($event) {
          $emittedEvents[] = $event;
        }
      }
    }
    return $emittedEvents;
  }


  /**
   * Responds to GET requests.
   *
   * @param string $tx_hash
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($tx_hash = NULL) {
    $cached = TRUE; // Default: TRUE - Helpful for development.
    $resp = NULL;

    $tx_hash = strtolower(Xss::filter($tx_hash));
    if (strlen($tx_hash) !== 66 ||
      !EthereumStatic::isValidHexData($tx_hash) ||
      !$this->currentUser->hasPermission('restful get event_processor')
    ) {
      $resp = new ResourceResponse(NULL, 403);
    }

    if (!$resp) {
      try {
        $emittedEvents = [];
        $txData = \Drupal::service('ethereum.client')
          ->eth_getTransactionReceipt(new EthD32($tx_hash));
        if ($txData) {
          foreach ($txData->logs as $filterChange) {
            $emittedEvents = array_merge($emittedEvents, self::processContractLogs($filterChange));
          }
        }
        if (count($emittedEvents)) {
          foreach ($emittedEvents as $event) {
            \Drupal::logger('ethereum_smartcontract')->info(
              $this->t('Processed Event "@event"', ['@event' => $event->getName()]) .
              ' <br />' . str_replace("\n", '<br />', $event->getLog()));
          }
        }
        $resp = new ResourceResponse(['success' => TRUE]);
      } catch (\Exception $e) {
        \Drupal::logger('ethereum_smartcontract')->error($e->getMessage());
        $resp = new ResourceResponse(['error' => $this->t('Could not process your transaction.')], 503);
      }
    }

    return $cached ? $resp : $resp->addCacheableDependency(['#cache' => ['max-age' => 0]]);
  }
}
