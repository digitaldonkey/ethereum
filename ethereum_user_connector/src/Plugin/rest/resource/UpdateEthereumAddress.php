<?php

namespace Drupal\ethereum_user_connector\Plugin\rest\resource;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Drupal;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Ethereum\EthereumStatic;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Xss;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "update_ethereum_address",
 *   label = @Translation("Update Ethereum account"),
 *   uri_paths = {
 *     "canonical" = "/ethereum/update-account/{address}"
 *   }
 * )
 */
class UpdateEthereumAddress extends ResourceBase {

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
   * @param string $address
   *   New Ethereum Address.
   *
   * @throws AccessDeniedHttpException
   *   Throws if user not authorized.
   * @throws InvalidArgumentException
   *   Throws exception if $hash is not an address.
   * @throws \Exception
   *   If user update failed.
   *
   * @return ResourceResponse
   *   With validated data.
   *   See ethereum_user_connector\Controller\verifyUserByHash().
   */
  public function get($address) {
    $cached = TRUE; // Default: TRUE - Helpful for development.

    // @todo Add setting for allow anonymous signup?
    // This requires a different storage mechanism for the Hash.
    // If the user is not authenticated we can just temporarily store the
    // Hash somewhere. On verify we would make sure that if the user was
    // not registered yet and we have a submitted Hash.
    // Thinking more about that when we have EcRecover
    // (see branch feature-ethereum_signup) merged. Think with EcRecover signup
    // is easier and we can submit to the registry after.

    if ($this->currentUser->isAuthenticated()) {

      $address = strtolower(Xss::filter($address));

      if (EthereumStatic::isValidAddress($address)) {

        $user = User::load($this->currentUser->id());
        $old_address = $user->get('field_ethereum_address')->getString();
        if ($old_address !== $address) {
          // Drupal Hash and validation status will be updated hook_pre_save.
          $user->set('field_ethereum_address', $address);
          if ($user->save() !== SAVED_UPDATED) {
            throw new \Exception('Error updating user Ethereum status for UID: ' . $user->id());
          }
          $message = $this->t('Successfully updated account address @old with @new.', array('@old' => $old_address, '@new' => $address));
          Drupal::logger('ethereum_user_connector')->notice($message);
        }
        $hash = $user->get('field_ethereum_drupal_hash')->getString();

        $resp = new ResourceResponse(array('hash' => $hash));
        return $cached ? $resp : $resp->addCacheableDependency(['#cache' => ['max-age' => 0]]);
      }
      throw new InvalidArgumentException();
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
