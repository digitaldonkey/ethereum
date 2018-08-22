<?php
/**
 * @file
 * Contains
 *   \Drupal\ethereum_smartcontract\Form\SmartContractImportMultistepFormBase.
 */

namespace Drupal\ethereum_smartcontract\Form;


use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


abstract class SmartContractImportMultistepFormBase extends EntityForm {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;


  protected $entityQuery;

  /**
   * Constructs a
   * \Drupal\ethereum_smartcontract\Form\SmartContractImportMultistepFormBase.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    SessionManagerInterface $session_manager,
    AccountInterface $current_user,
    QueryFactory $entity_query
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->entityQuery = $entity_query;
    $this->store = $this->tempStoreFactory->get('smartcontract_import');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if (!isset($_SESSION['multistep_form_holds_session'])) {
      $_SESSION['multistep_form_holds_session'] = TRUE;
      $this->sessionManager->start();
    }

    $form = [];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multi step form.
   */
  protected function deleteStore() {
    $keys = ['contracts', 'select_contract', 'import_path'];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }


  /**
   * Helper to create a Options field.
   *
   * @param array $contracts
   *
   * @return array
   */
  protected static function selectifyContracts(array $contracts) {
    $ret = [];
    foreach ($contracts as $c) {
      // $date = \Drupal::service('date.formatter')->format(strtotime($c->updatedAt));
      $ret[$c->contractName] = $c->contractName . ' (' . $c->updatedAt . ')';
    }
    return $ret;
  }


}
