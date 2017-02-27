<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\ethereum_smartcontract\Entity\SmartContract;
//use Ethereum\EthereumClient;
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
    return [

    ] + parent::defaultSettings();
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

    // Module settings.
    $config = Drupal::config('ethereum_user_connector.settings');

    $contract_call_data = '0x' . $config->get('contract_new_user_call');
    $param = $connector->client->strToHex($entity->field_ethereum_drupal_hash->value);
    $ajax_verify_url = $base_path . 'ethereum/validate/';

    $element['value'] = $element + [
      '#theme' => 'field_ethereum_account_status',
      '#children' => $items,
      '#user_ethereum_address' => $entity->field_ethereum_address->value,
      '#status_number' => $entity->field_ethereum_account_status->value,
      '#status' => isset($status_map[$entity->field_ethereum_account_status->value]) ? $status_map[$entity->field_ethereum_account_status->value] : $status_map[0],
      '#ethereum_drupal_hash' => $entity->field_ethereum_drupal_hash->value,
      '#attached' => array(
        'library' => array(
          'ethereum_user_connector/ethereum-user-connector',
        ),
        'drupalSettings' => array (
          'ethereumUserConnector' => array (
            'contractAddress' => $connector->get_contract_address(),
            'contractCode' => $connector::ContractCode,
            'userEthereumAddress' => $entity->field_ethereum_address->value,
            'contractNewUserCall' => $contract_call_data . $param,
            'verificationUrl' => $ajax_verify_url,
            'drupalHash' => $entity->field_ethereum_drupal_hash->value,
          )
        )
      ),
    ];
    return $element;
  }
}
