<?php

namespace Drupal\ethereum\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ethereum_address' widget.
 *
 * @FieldWidget(
 *   id = "ethereum_address",
 *   label = @Translation("Ethereum address"),
 *   field_types = {
 *     "ethereum_address"
 *   }
 * )
 */
class EthereumAddressWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 42,
      'placeholder' => '0xaec98826319ef42aab9530a23306d5a9b113e23d',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Set a custom validation pattern.
    // @see https://www.w3schools.com/code/tryit.asp?filename=FEBTROZXKYL9
    $element['value']['#pattern'] = '0[x,X]{1}[0-9,a-f,A-F]{40}';
    $element['value']['#attributes']['title'] = $this->t('An Ethereum address must start with "0x", followed by 40 hexadecimal characters. For example: 0xaec98826319ef42aab9530a23306d5a9b113e23d');

    return $element;
  }

}
