<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ethereum_user_connector\Controller\EthereumUserConnectorController;

/**
 * Plugin implementation of the 'ethereu_status_widget' widget.
 *
 * @FieldWidget(
 *   id = "ethereum_status_widget",
 *   label = @Translation("Ethereum Account status"),
 *   field_types = {
 *     "ethereum_status"
 *   }
 * )
 */
class EthereumStatusWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
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

    $connector = new EthereumUserConnectorController();
    $client = $connector->client;

    // Module settings.
    $config = Drupal::config('ethereum_user_connector.settings');

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
            'contractAddress' => $connector->getContractAddress(),
            'userEthereumAddress' => $entity->field_ethereum_address->value,
            'drupalHash' => $entity->field_ethereum_drupal_hash->value,
            'validateContractCall' => $client->ensureHexPrefix($config->get('contract_contractExists_call')),
            'contractNewUserCall' => $client->ensureHexPrefix($config->get('contract_newUser_call')),
            'verificationUrl' => $base_path . 'ethereum/validate/',
            'updateAccountUrl' => $base_path . 'ethereum/update-account/',
          ),
        ),
      ),
    ];
    return $element;
  }

}
