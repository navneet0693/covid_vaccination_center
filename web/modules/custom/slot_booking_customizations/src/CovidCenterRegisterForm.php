<?php

namespace Drupal\slot_booking_customizations;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

class CovidCenterRegisterForm extends RegisterForm {

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entity;

    // Unset field for on registeration form.
    if ($account->isAnonymous()) {
      unset($form['field_covid_center']);
    }

    return $form;
  }

}
