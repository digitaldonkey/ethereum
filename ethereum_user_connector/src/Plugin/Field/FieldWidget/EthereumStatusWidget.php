<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

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

    $X = FALSE;

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

    $entity = $items->getEntity();
    $settings = $entity->field_ethereum_account_status->getSettings();
    $status_map = $entity->field_ethereum_account_status->getSettings()['allowed_values'];

    $config = \Drupal::config('ethereum_user_connector.settings');
    // $config->get('contract_address');


    //$X = $entity->getFieldDefinition('field_ethereum_account_status');
    // GET THE ABI ?!

    $element['value'] = $element + [
      '#theme' => 'field_ethereum_account_status',
      '#children' => $items,
      '#ethereum_address' => $entity->field_ethereum_address->value,
      '#status_number' => $entity->field_ethereum_account_status->value,
      '#status' => isset($status_map[$entity->field_ethereum_account_status->value]) ? $status_map[$entity->field_ethereum_account_status->value] : $status_map[0],
      '#ethereum_drupal_hash' => $entity->field_ethereum_drupal_hash->value,
      '#attached' => array(
         'library' => array('ethereum_user_connector/ethereum-user-connector'),
      ),
      '#attributes' => array(
          'class' => 'ethereum-connection-status',
        ),
      ];

    return $element;
  }

}
