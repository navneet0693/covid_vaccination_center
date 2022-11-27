<?php

namespace Drupal\slot_booking_customizations;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;

/**
 * Form handler for the node edit forms.
 */
class CovidCenterNodeForm extends NodeForm {

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // We want to restrict the customizations only if the vaccination center
    // information is being altered/edited.
    if ($this->operation == 'edit' && $this->entity->bundle() == 'covid_center') {
      // Disable the available slots text field, we don't want to allow
      // the user to alter the available slots information as it should
      // added only at the time creating the covid center.
      $form['field_available_slots']['widget'][0]['#disabled'] = 'TRUE';
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->bundle() == 'covid_center') {
      // Get the available slots for this covid center.
      $available_slots = $form_state->getValue('field_available_slots')[0]['value'];
      // Count the number of registered users as of now.
      $count = count(array_filter(array_column($form_state->getValue('field_registered_users'), 'target_id')));
      // We want to check if the registered users are more than available slot.
      // If it is, we want to throw an error.
      if ($count > $available_slots) {
        $form_state->setErrorByName('field_registered_users',
          $this->t('Only @count are allowed to register in this vaccination center as per available slots.', [
            '@count' => $available_slots,
          ]));
      }
    }
    return $this->buildEntity($form, $form_state);
  }

}
