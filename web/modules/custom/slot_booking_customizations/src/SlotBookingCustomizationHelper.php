<?php

namespace Drupal\slot_booking_customizations;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

class SlotBookingCustomizationHelper {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Entity Type Manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Currrent user object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager object.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the user's current city.
   *
   * @return int
   *   Term ID for the city.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserCity() {
    // Let's load the full user entity object.
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    // Check if the above statement has returned a valid object.
    // Check if the user object has field_city available.
    // Check if the city field has a value.
    if ($user instanceof UserInterface
      && $user->hasField('field_city')
      && !$user->get('field_city')->isEmpty()
    ) {
      // Get the field value.
      $city = $user->get('field_city')->getValue();

      // Get the term ID.
      $term_id = (int) $city[0]['target_id'];
      if (!empty($term_id)) {
        return $term_id;
      }
    }
    return 0;
  }

}
