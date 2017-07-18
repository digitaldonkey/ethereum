<?php

/**
 * @file
 * Contains \Drupal\ethereum\Controller\EthereumController.
 */

namespace Drupal\ethereum_signup\Controller;

use Drupal;
use Drupal\ethereum\Controller\EthereumController;
use Ethereum\EthD;
use Ethereum\EthD32;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\Core\Database\Database;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\User;

/**
 * Controller routines for Ethereum routes.
 */
class EthereumSignupController extends EthereumController {

  // Provider name for Authmap module.
  const PROVIDER = 'ethereum_signup';

  private $log_in_user,
    $terms_text,
    $register_role,
    $require_mail_confirm,
    $require_admin_confirm,
    $login_redirect;

  /**
   * @inheritdoc
   */
  public function __construct($host = FALSE) {
    parent::__construct($host);
    global $base_url;

    $cnf = $this->config('ethereum_signup.settings');

    // We will log in the user, if admin approval or
    // email-confirmation is not required.
    $this->log_in_user = (!$cnf->get('require_mail_confirm')
                       && !$cnf->get('require_admin_confirm'));

    $this->terms_text = $cnf->get('register_terms_text');
    $this->welcome_text = $cnf->get('login_welcome_text');

    $this->register_role = $cnf->get('register_role');
    $this->require_mail_confirm = $cnf->get('require_mail_confirm');
    $this->require_admin_confirm = $cnf->get('require_admin_confirm');
    $this->login_redirect = $base_url . '/' . $cnf->get('login_redirect');
  }

  public function createChallenge($address) {
    $date  = Drupal::service('date.formatter')->format(time());
    $challenge_text = str_replace('#date', $date, $this->welcome_text);
    $store = Drupal::service('user.shared_tempstore')->get(self::PROVIDER);
    $store->set($address, $challenge_text);

    $challenge_text = $store->get($address);

    return array(
      'success' => TRUE,
      'challenge' => $challenge_text,
    );
  }

  public function verifyLogin($address, $signature) {
    $error = NULL;
    $store = Drupal::service('user.shared_tempstore')->get(self::PROVIDER);

    $challenge_text = $store->get($address);
    $store->delete($address);

    $restored_address = $this->restoreAddress($challenge_text, $signature);

    // Authmap load / Account exists?
    if ($restored_address === $address) {
      $authmap = new Authmap(Database::getConnection());
      $uid =  $authmap->getUid($restored_address, self::PROVIDER);
      if ($uid) {
        $user = User::load($uid);
        user_login_finalize($user);
      }
      else {
        $error = $this->t('User with ' . $address . ' does not exist.');
      }
    }
    else {
      $error = $this->t('Signature verification failed.');
    }

    if (!$error) {
      return array(
        'success' => TRUE,
        'reload' => $this->login_redirect,
      );
    }
    else {
      return array(
        'success' => FALSE,
        'error' => $error,
      );
    }
  }


  public static function isEthereumSignupUser(User $user) {
    $authmap = new Authmap(Database::getConnection());
    return (bool) $authmap->get($user->id(), self::PROVIDER);
  }
  /**
   * Restore Ethereum address from text and Signature.
   *
   * @param string $text
   *   Text signed with web3.personalSign() by user (Utf-8)
   * @param string $signature
   *   Signature (Hex)
   *
   * @return string Ethereum address (Hex with 0x-prefix, 42 letters).
   */
  public function restoreAddress($text, $signature) {
    $message_hash = $this->client->phpKeccak256($this->client->personalSignAddHeader($text));
    $sig = $this->client->parseSignature(new EthD($signature));
    return $this->client->phpEcRecover(new EthD32($message_hash), $sig['v'], $sig['r'], $sig['s']);
  }

  /**
   * Verify User by Drupal hash.
   *
   * @param string $address
   *   Ethereum Address (0x-prefixed 42chars total).
   * @param string $signature
   *   Ethereum Address (0x-prefixed ??? chars total). // TODO Signature length?
   * @param string|FALSE $email
   *   User email if required or false if not.
   * @param bool $require_email
   *   True if email is mandatory.
   *
   *
   * @return array
   *    The current status array for registration.
   *
   * @throws \Exception
   */
  public function verifyRegistration($address, $signature, $email, $require_email) {

    // Initialize expected response.
    $resp = (object) array(
      'success' => FALSE,
      'account_exists' => NULL,
      'error' => NULL,
      // TODO The last two are actually not required. Consider removing to KISS.
      'uid' => NULL,
      'ethereum_address' => $address,
    );

    // Verify signature.
    $restored_addr = $this->restoreAddress($this->terms_text, $signature);
    $signature_verified = ($address === $restored_addr);

    if(!$signature_verified) {
      $resp->error = $this->t('Signature verification failed for ' . $address);
    }

    // Check signature and if not address is not yet registered.
    $new_user = NULL;

    $authmap = new Authmap(Database::getConnection());
    $existing_user_uid =  $authmap->getUid($address, self::PROVIDER);

    // User with $address exists.
    if (intval($existing_user_uid) > 0) {
      // User exists.
      $resp->account_exists = TRUE;
      $resp->uid = $existing_user_uid;
      $resp->error = $this->t('A user with Ethereum address ' . $address . ' already exists.');
    }

    // Create new User.
    if (!$resp->account_exists && $signature_verified ) {

      $auth = new ExternalAuth(
        Drupal::entityManager(),
        $authmap,
        Drupal::logger('ethereum_signup'),
        Drupal::service('event_dispatcher')
      );

      // Register terms and signature will be saved inn authmap.
      $authmap_data = array(
        'register_terms' => $this->terms_text,
        'signature' => $signature,
      );

      // Default account data.
      $account_data = array(
        'roles' => array(
          RoleInterface::AUTHENTICATED_ID => 'authenticated user',
        ),
        'status' => 0,
      );

      // Required email missing.
      if ($require_email && !$email) {
        $resp->error = $this->t('Email is required.');
      }

      // Email unique check.
      if ($email) {
        if (user_load_by_mail($email) != FALSE) {
          $resp->error = $this->t('User with this email already exists.');
          $email = 'email_in_use';
        }
        else {
          $account_data['mail'] = $email;
        }
      }
      if ((!$require_email || ($require_email && $email)) && $email !== 'email_in_use') {


        $new_user = $auth->register($address, self::PROVIDER, $account_data, $authmap_data);

        // Add role.
        if ($this->register_role !== RoleInterface::AUTHENTICATED_ID) {
          $new_user->addRole($this->register_role);
        }

        // Change name to Address only.
        // Remove prefix from name provided by authmap.
        $new_user->setUsername($address);
        $new_user->save();

        // Jay! User validated and Confirmed.
        $resp->success = TRUE;
        $resp->uid = $new_user->id();

        // Jay! User validated and Confirmed.


        // Check if Admin or email approval is required
        if ($this->log_in_user || $this->require_mail_confirm) {
          $new_user->set('status', 1);
          $new_user->save();
        }

        // TODO MAYBE MESSAGES FOR WAITING APPROVAL

        if ($this->require_admin_confirm) {
          _user_mail_notify('register_pending_approval', $new_user);
        }

        if ($this->require_mail_confirm) {
          _user_mail_notify('register_no_approval_required', $new_user);
        }

        // Login user.
        if ($this->log_in_user) {
          if ($this->log_in_user) {
            $auth->userLoginFinalize($new_user, $address, self::PROVIDER);
            $resp->reload = $this->login_redirect;
          }
        }
      }
    }

    // ERRORS:
    // - already registered
    //   --> start log-in
    // - Signature decode fail
    // - System (sig decode) fail
    // - Pending Admin approval
    // - Require email approval

    return (array) $resp;
  }

}
