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
      'use_current_network' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['use_current_network'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use current network'),
      '#default_value' => $this->getSetting('use_current_network'),
      '#description' => $this->t('The network selection will not be displayed and the current Ethereum network will be used as a default value.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('use_current_network')) {
      $summary[] = $this->t('Use current network as default');
    }

    return $summary;
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
    $element['value']['#maxlength'] = $this->getSetting('size');

    /** @var \Drupal\ethereum\EthereumManagerInterface $ethereum_manager */
    $ethereum_manager = \Drupal::service('ethereum.manager');
    $network_options = $ethereum_manager->getNetworksAsOptions();

    if (!$this->getSetting('use_current_network')) {
      $element['network'] = [
        '#type' => 'select',
        '#options' => $network_options,
        '#default_value' => isset($items[$delta]->network) ? $items[$delta]->network : $ethereum_manager->getCurrentNetworkId(),
        '#weight' => 10,
      ];
      $element['#type'] = 'container';
      $element['#attributes']['class'][] = 'container-inline';
    }
    else {
      $element['network'] = [
        '#type' => 'value',
        '#value' => $ethereum_manager->getCurrentNetworkId(),
      ];
    }

    return $element;
  }

}
