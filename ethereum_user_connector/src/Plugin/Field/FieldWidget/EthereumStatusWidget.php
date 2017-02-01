<?php

namespace Drupal\ethereum_user_connector\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ethereu_status_widget' widget.
 *
 * @FieldWidget(
 *   id = "ethereu_status_widget",
 *   label = @Translation("Ethereu status widget"),
 *   field_types = {
 *     "list_string"
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

    $Y = $form_state->getStorage();

    var_dump(\Drupal\user\UserInterface);

//    var_dump($form_state->getValue('field_ethereum_address'));

//    var_dump($items);

    // TODO Make Template and add Submission stuff....

    // WHAT WE NEED
    // - STATUS ID
    // - STATUS NAME
    // ADDRESS
    // HASH (AT LEAST IN JS)

//    $element['value'] = $element + [
//        '#theme' => 'field_ethereum_address_status',
//        'variables' => array ('#adress'=>"bbbbbbb"),
//      ];

    $element['value'] = $element + [
      '#theme' => 'field_ethereum_address_status',
      '#children' => $items,
      '#address' => 'Haöllos',
      '#attached' => array(
//          'library' => array('core/html5shiv'),
        ),
//      '#attributes' => array(
//          'class' => 'ethereum-connection-status',
//        ),
//       '#markup' => 'Hello',
    ];

    return $element;
  }

}
