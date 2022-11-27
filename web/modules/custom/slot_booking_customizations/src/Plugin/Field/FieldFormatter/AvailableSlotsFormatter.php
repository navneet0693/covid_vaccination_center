<?php

namespace Drupal\slot_booking_customizations\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'available slots' formatter.
 *
 * @FieldFormatter(
 *   id = "available_slots_formatter",
 *   label = @Translation("Available Slots Formatter"),
 *   description = @Translation("Display the number of available slots in the covid center."),
 *   field_types = {
 *     "string"
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class AvailableSlotsFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Get the main node object as we need the current node for checking
    // bundle type and value of field_registered_users and compare it with
    // available slots value.
    $node = $items->getEntity();
    if ($node->bundle() == 'covid_center') {
      // Get the value of available slots.
      $available_slots = $elements[0]['#context']['value'];
      // Get the count of registered users.
      $count = count(array_column($node->get('field_registered_users')->getValue(), 'target_id'));
      // Check if the count is not greater than available slots.
      if (!empty($count)
        && $count <= $available_slots
      ) {
        // Adjust the value of available slots by reducing the number of users
        // registered in this vaccination center.
        $elements[0]['#context']['value'] -= $count;
      }
    }
    return $elements;
  }

}
