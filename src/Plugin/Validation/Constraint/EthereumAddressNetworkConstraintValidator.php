<?php

namespace Drupal\ethereum\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\ethereum\Controller\EthereumController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EthereumAddressNetwork constraint.
 */
class EthereumAddressNetworkConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * The ethereum_address storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ethereumAddressStorage;

  /**
   * Constructs a new EthereumAddressNetworkConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $ethereum_address_storage
   *   The ethereum_address storage handler.
   */
  public function __construct(EntityStorageInterface $ethereum_address_storage) {
    $this->ethereumAddressStorage = $ethereum_address_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager')->getStorage('ethereum_address'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\ethereum\EthereumAddressInterface $entity */
    $network_id = $entity->getNetworkId();
    $existing_address = $this->ethereumAddressStorage->loadByProperties([
      'address' => $entity->getAddress(),
      'network' => $network_id,
    ]);

    if ($existing_address) {
      $networks = EthereumController::getNetworks();
      $this->context->buildViolation($constraint->message, ['%network_label' => $networks[$network_id]['label']])
        ->atPath('address')
        ->addViolation();
    }
  }

}
