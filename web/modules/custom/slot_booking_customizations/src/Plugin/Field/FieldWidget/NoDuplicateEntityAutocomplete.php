<?php

namespace Drupal\slot_booking_customizations\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "no_duplicate_entity_reference_autocomplete",
 *   label = @Translation("Autocomplete - Disallow Duplicates"),
 *   description = @Translation("An autocomplete text field that validates against duplicates."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class NoDuplicateEntityAutocomplete extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    parent::afterBuild($element, $form_state);

    $class = get_class();
    $element['#element_validate'][] = [$class, 'validateNoDuplicates'];

    return $element;
  }

  /**
   * Set a form error if there are duplicate entity ids.
   */
  public static function validateNoDuplicates(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Get all the inputs.
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Extract the IDs.
    $ids = array_filter(array_column($input, 'target_id'));

    // Check that there aren't duplicate entity_id values.
    if (count($ids) !== count(array_flip($ids))) {
      $form_state->setError($element, 'Field "' . $element['#title'] . '" doesn\'t allow duplicates.');
    }

  }

}
