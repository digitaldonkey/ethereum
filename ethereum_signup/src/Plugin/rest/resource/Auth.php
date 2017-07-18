<?php

namespace Drupal\ethereum_signup\Plugin\rest\resource;

use Drupal;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Ethereum;
use Drupal\Component\Utility\Xss;
use Drupal\ethereum_signup\Controller\EthereumSignupController;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "ethereum_signup_auth",
 *   label = @Translation("Challenge response login."),
 *   uri_paths = {
 *     "create" = "/ethereum/signup/auth",
 *     "https://www.drupal.org/link-relations/create" = "/ethereum/signup/auth"
 *   }
 * )
 */
class Auth extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  public $require_mail;


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

    // TODO Maybe not required?
    $this->currentUser = $current_user;

    $cnf = Drupal::config('ethereum_signup.settings');
    $this->require_mail = ($cnf->get('require_mail') || $cnf->get('require_mail_confirm'));
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
      $container->get('logger.factory')->get('ethereum_signup'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * - If address is set we will create a challenge.
   * - If a signature is set we try to verify.
   *
   * @param array $data
   *  Data array send as a Json.
   *
   *  {
   *   "address": "0x4097752d39b5fb5c9b2490d53fb3d50f355dad7a",
   *  }
   *
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   *
   * @return ResourceResponse
   *   With validated data.
   *   See ethereum_user_connector\Controller\verifyUserByHash().
   */
  public function post(Array $data) {
    $response = NULL;
    $address = NULL;
    $signature = NULL;

    if (isset($data['address'])) {
      $address = $this->validateAddress($data);
    }
    if (isset($data['signature'])) {
      $signature = $this->validateSignature($data);
    }

    if ($data['action'] === "challenge" && is_string($address)) {
      $ethSign = new EthereumSignupController();
      $response = $ethSign->createChallenge($address);
    }

    if ($data['action'] === "response" && is_string($address) && is_string($signature)) {
      $ethSign = new EthereumSignupController();
      $response = $ethSign->verifyLogin($address, $signature);
    }


    if ($response) {
      $return = new ResourceResponse($response);
      $return->addCacheableDependency(FALSE);
      return $return;
    }
    else {
      // throw new AccessDeniedHttpException();
      throw new InvalidArgumentException();
    }
  }




  /**
   * Verify address.
   *
   *  @param array $data
   *    User input corresponding with javascript.
   *
   * @return string|FALSE
   *    Cleaned, validated data array or false.
   */
  private function validateSignature(Array $data) {
    try {
      $signature = Xss::filter($data['signature']);
      if (substr($signature, 0, 2) === '0x' &&
        strlen($signature) >= 132 &&
        // TODO What is the length of a signature?
        // strlen($signature) <= 132 &&
        !ctype_xdigit(substr($signature, 2))
      ) {
        throw new \Exception('Invalid param signature: ' . $signature);
      }
      return $signature;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Verify address.
   *
   *  @param array $data
   *    User input corresponding with javascript.
   *
   * @return string|FALSE
   *    Cleaned, validated data array or false.
   */
  private function validateAddress(Array $data) {
    try {

      $address = Xss::filter($data['address']);
      if (substr($address, 0, 2) === '0x' &&
        strlen($address) === 42 &&
        !ctype_xdigit(substr($address, 2))
      ) {
        throw new \Exception('Invalid param address: ' . $address);
      }
      return $address;
    } catch (\Exception $e) {
      return FALSE;
    }
  }
}
