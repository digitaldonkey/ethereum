<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings']
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

    $user = $items->getEntity();
    $status_map = $user->field_ethereum_account_status->getSettings()['allowed_values'];

    $element['value'] = $element + [
      '#theme' => 'field_ethereum_account_status',
      '#cache' => [
        // @todo This cache setup does not seem to work.
        'tags' => [
          'config:ethereum_smartcontract.contract.register_drupal',
          'config:ethereum.settings',
        ],
        'contexts' => ['user']
      ],
      '#children' => $items,
      '#user_ethereum_address' => $user->field_ethereum_address->getString(),
      '#status_number' => $user->field_ethereum_account_status->getString(),
      '#status_map' => json_encode($status_map),
      '#status' => isset($status_map[$user->field_ethereum_account_status->getString()]) ? $status_map[$user->field_ethereum_account_status->getString()] : $status_map[0],
      '#ethereum_drupal_hash' => $user->field_ethereum_drupal_hash->getString(),
      '#attached' => array(
        'library' => array(
          'ethereum_user_connector/ethereum-user-connector',
          // Add register_drupal (Currently actually all active contracts).
          'ethereum_smartcontract/contracts'
        ),
        'drupalSettings' => array(
          'ethereumUserConnector' => array(
            'drupalHash' => $user->field_ethereum_drupal_hash->getString(),
            'verificationUrl' => $base_path . 'ethereum/validate/',
            'updateAccountUrl' => $base_path . 'ethereum/update-account/',
          ),
        ),
      ),
    ];
    return $element;
  }
}
