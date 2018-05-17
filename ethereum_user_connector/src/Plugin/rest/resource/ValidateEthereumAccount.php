<?php

namespace Drupal\ethereum_user_connector\Plugin\rest\resource;

use Drupal;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;
use Drupal\Component\Utility\Xss;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "validate_ethereum_account",
 *   label = @Translation("Validate ethereum account"),
 *   uri_paths = {
 *     "canonical" = "/ethereum/validate/{hash}"
 *   }
 * )
 */
class ValidateEthereumAccount extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
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
      $container->get('logger.factory')->get('ethereum_user_connector'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $hash
   *   Drupal hash requested in url param.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   * @throws \Exception
   *
   * @return \Drupal\rest\ResourceResponse
   *   With validated data.
   *   See ethereum_user_connector\Controller\verifyUserByHash().
   */
  public function get($hash) {
    if ($this->currentUser->isAuthenticated()) {
      $controller = \Drupal::service('class_resolver')->getInstanceFromDefinition(EthereumUserConnectorController::class);
      $validation = $controller->verifyUserByHash(Xss::filter($hash));
      if (is_array($validation) && $validation['success']) {
        $message = $this->t('Successfully validated account ' . $validation['ethereum_address'] . ' with hash ' . $validation['ethereum_drupal_hash']);
        Drupal::logger('ethereum_user_connector')->notice($message);
      }
      return new ResourceResponse($validation);
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
