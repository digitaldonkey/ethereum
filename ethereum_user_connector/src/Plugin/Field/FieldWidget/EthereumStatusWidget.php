<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;
use Ethereum\Ethereum;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ethereum_status_widget' widget.
 *
 * @FieldWidget(
 *   id = "ethereum_status_widget",
 *   label = @Translation("Ethereum Account status"),
 *   field_types = {
 *     "ethereum_status"
 *   }
 * )
 */
class EthereumStatusWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Ethereum JsonRPC client.
   *
   * @var \Ethereum\Ethereum
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a EthereumStatusWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Ethereum\Ethereum $ethereum_client
   *   The Ethereum JsonRPC client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Ethereum $ethereum_client, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->client = $ethereum_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\WidgetPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('ethereum.client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Shows Ethereum address connection status and allows user to verify submitted address by signing a validation hash into the login smart contract.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    global $base_path;

    $entity = $items->getEntity();
    $status_map = $entity->field_ethereum_account_status->getSettings()['allowed_values'];

    // Module settings.
    $config = $this->configFactory->get('ethereum_user_connector.settings');

    $element['value'] = $element + [
      '#theme' => 'field_ethereum_account_status',
      '#children' => $items,
      '#user_ethereum_address' => $entity->field_ethereum_address->value,
      '#status_number' => $entity->field_ethereum_account_status->value,
      '#status_map' => json_encode($status_map),
      '#status' => isset($status_map[$entity->field_ethereum_account_status->value]) ? $status_map[$entity->field_ethereum_account_status->value] : $status_map[0],
      '#ethereum_drupal_hash' => $entity->field_ethereum_drupal_hash->value,
      '#attached' => array(
        'library' => array(
          'ethereum_user_connector/ethereum-user-connector',
        ),
        'drupalSettings' => array(
          'ethereumUserConnector' => array(
            'contractAddress' => EthereumUserConnectorController::getContractAddress(),
            'userEthereumAddress' => $entity->field_ethereum_address->value,
            'drupalHash' => $entity->field_ethereum_drupal_hash->value,
            'validateContractCall' => $this->client->ensureHexPrefix($config->get('contract_contractExists_call')),
            'contractNewUserCall' => $this->client->ensureHexPrefix($config->get('contract_newUser_call')),
            'verificationUrl' => $base_path . 'ethereum/validate/',
            'updateAccountUrl' => $base_path . 'ethereum/update-account/',
          ),
        ),
      ),
    ];
    return $element;
  }

}
