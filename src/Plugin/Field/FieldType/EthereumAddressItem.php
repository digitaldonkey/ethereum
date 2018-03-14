<?php

namespace Drupal\ethereum\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ethereum_address' field type.
 *
 * @FieldType(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   description = @Translation("Provides a field for Ethereum addresses."),
 *   default_widget = "ethereum_address",
 *   default_formatter = "basic_string"
 * )
 */
class EthereumAddressItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 42,
      'is_ascii' => TRUE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Regex' => [
            'pattern' => '/^0x[0-9a-f]{40}/is',
            'message' => $this->t('An Ethereum address must start with "0x", followed by 40 hexadecimal characters. For example: "0x0000000000000000000000000000000000000000".'),
          ]
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = "0x0000000000000000000000000000000000000000";
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // Don't inherit the parent storage settings form as the maximum length is
    // not configurable for Ethereum addresses.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = strtolower($this->value);
  }

}
