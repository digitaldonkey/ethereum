<?php

namespace Drupal\ethereum_signup\Plugin\rest\resource;

use Drupal;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Ethereum;
use Drupal\Component\Utility\Xss;
use Drupal\ethereum_signup\Controller\EthereumSignupController;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "ethereum_signup_verify",
 *   label = @Translation("Validate elipic curve signature"),
 *   uri_paths = {
 *     "create" = "/ethereum/signup/register",
 *     "https://www.drupal.org/link-relations/create" = "/ethereum/signup/register"
 *   }
 * )
 */
class VerifySignup extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
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

   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param array $data
   *  Data array send as a Json. If email is required depends on settings. E.g:
   *
   *  {
   *   "address": "0x4097752d39b5fb5c9b2490d53fb3d50f355dad7a",
   *   "signature": "0x627c13c55329e7eb3b377930e00b1186bf3115a9b4a45218eb8530caefbf125502edfaeb33a4c5f848b55ad5438c6e50160397bd47811f70df2173e536b4dd2c1b",
   *   "email": "email@donkeymedia.eu"
   *  }
   *
   * Note:
   * This signature is valid if the settings register_terms_text is:
   * 'I want to create a Account on this website. By I signing this text (using Ethereum personal_sign) I agree to the following conditions.'
   *
   * @throws \Exception
   *
   * @return ResourceResponse
   *   With validated data.
   *   See ethereum_user_connector\Controller\verifyUserByHash().
   */
  public function post(Array $data) {

    $data = $this->validateSignupParams($data);

    if (is_array($data)) {
      $ethSign = new EthereumSignupController();
      $response = $ethSign->verifyRegistration($data['address'], $data['signature'], $data['email'], $this->require_mail);

      if (!$response['error']) {
        $message = $this->t('Successfully validated account ' . $response['ethereum_address'] . ' with hash signature ' . $data['signature']);
        Drupal::logger('ethereum_signup')->notice($message);
        // @todo Maybe log failing attempts with IP.
      }
      $return = new ResourceResponse($response);
      $return->addCacheableDependency(FALSE);
      return $return;
    }
    else {
      // If tha params are wrong, somebody may try to cheat.
      // @todo Maybe log failing attempts with IP. Here: Wrong params.
      // throw new AccessDeniedHttpException();
      throw new InvalidArgumentException();
    }
  }

  /**
   * Clean and verify params.
   *
   * Validation for registration depends on mail verification settings.
   *
   *  @param array $data
   *    User input corresponding with javascript.
   *
   * @return array|FALSE
   *    Cleaned, validated data array or false.
   */
  private function validateSignupParams(Array $data) {
    try {

      $required_params = (
        isset($data['address']) &&
        isset($data['signature']) &&
        (isset($data['email']) || $this->require_mail === FALSE)
      );
      if (!$required_params) {
        throw new \Exception('Invalid params: Missing params');
      }

      $address = strtolower(Xss::filter($data['address']));
      if (substr($address, 0, 2) === '0x' &&
        strlen($address) === 42 &&
        !ctype_xdigit(substr($address, 2))
      ) {
        throw new \Exception('Invalid param address: ' . $address);
      }

      $signature = Xss::filter($data['signature']);
      if (substr($signature, 0, 2) === '0x' &&
        strlen($signature) >= 132 &&
        !ctype_xdigit(substr($signature, 2))
      ) {
        throw new \Exception('Invalid param signature: ' . $signature);
      }

      $email = FALSE;
      if (isset($data['email'])) {
        $email = Xss::filter($data['email']);
        if ($this->require_mail) {
          if (!Drupal::service('email.validator')->isValid($email)) {
            throw new \Exception('Invalid param signature: ' . $email);
          }
        }
      }

      return array(
        'address' => $address,
        'signature' => $signature,
        'email' => $email,
      );
    } catch (\Exception $e) {
      return FALSE;
    }
  }
}
