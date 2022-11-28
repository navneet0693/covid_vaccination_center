<?php

namespace Drupal\slot_booking_customizations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Provides a block with a button.
 *
 * @Block(
 *   id = "register_block",
 *   admin_label = @Translation("Register Block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     ),
 *     "user" = @ContextDefinition(
 *       "entity:user",
 *       label = @Translation("Current user")
 *     ),
 *   }
 * )
 */
class RegisterButton extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    if ($this->checkStatus()) {
      // User can view the register button on all the center in its city.
      // Once, the user is registered in any one of the city, we want to
      // inform the user about the same.
      // This will not appear on the covid vaccination center outside
      // user's city.
      /** @var \Drupal\node\NodeInterface $node */
      $node = $this->getContextValue('node');
      return [
        '#markup' => $this->t('You are already registered in @node_title', [
          '@node_title' => $node->getTitle(),
        ]),
      ];
    }
    else {
      // User can only register in its own city.
      // Check if the current covid centers city is same as current user city.
      if ($this->checkUserCity()
      && $this->checkSlotAvailability()
      ) {
        // User is not registered and present it with a registration link.
        // We build the AJAX link.
        $build['ajax_link']['link'] = [
          '#type' => 'link',
          '#title' => $this->t('Register'),
          // We have to ensure that Drupal's Ajax system is loaded.
          '#attached' => ['library' => ['core/drupal.ajax']],
          // We add the 'use-ajax' class so that Drupal's AJAX system can spring
          // into action.
          '#attributes' => [
            'class' => ['use-ajax button'],
            'id' => ['register-button-div'],
          ],
          // The URL for this link element is the route for our controller to
          // update users.
          '#url' => Url::fromRoute('slot_booking_customizations.register'),
        ];
        return $build;
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated());
  }

  /**
   * Checks the registration status of the current user.
   *
   * @return bool
   *   The registration status of the current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function checkStatus(): bool {
    // Get the user object from context.
    $user = $this->getContextValue('user');
    // Proceed only if we get the genuine user entity.
    if ($user instanceof UserInterface) {
      // Get the node object.
      $node = $this->getContextValue('node');
      // Only proceed if the node bundle is 'covid_center' and
      // current users doesn't have registered to any covid centers.
      if ($node instanceof NodeInterface
        && ($node->bundle() === 'covid_center')
        && !$user->get('field_covid_center')->isEmpty()
      ) {
        // Match the node ID in user entity's field_covid_center
        // and current node objects ID.
        // If they match, it means the current user is registered on the same
        // vaccination center.
        $value = $user->get('field_covid_center')->getValue()[0]['target_id'];
        if (!empty($value)
          && $value == $node->id()
        ) {
          return TRUE;
        }
      }
    }

    // Otherwise, user has not registered.
    return FALSE;
  }

  /**
   * Matches the covid center's and user's city.
   *
   * @return bool
   *   TRUE or FALSE depending upon the results of the match.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  private function checkUserCity(): bool {
    // Get the user object from context.
    $user = $this->getContextValue('user');
    // Proceed only if we get the genuine user entity.
    if ($user instanceof UserInterface) {
      // Get the node object.
      $node = $this->getContextValue('node');
      // Only proceed if the node bundle is 'covid_center' and
      // current users has the city filled.
      if ($node instanceof NodeInterface
        && ($node->bundle() === 'covid_center')
        && ($node->hasField('field_tags'))
        && !($node->get('field_tags')->isEmpty())
        && !$user->get('field_city')->isEmpty()
      ) {
        // Match the covid center city and user's city.
        // If they match, it means the current user is can register on the
        // vaccination center.
        $covid_center_city_tid = $node->get('field_tags')->getValue()[0]['target_id'];
        $user_city_tid = $user->get('field_city')->getValue()[0]['target_id'];

        if ($covid_center_city_tid == $user_city_tid) {
          return TRUE;
        }
      }
    }
    // Otherwise, user has not registered.
    return FALSE;

  }

  /**
   * Check the available slot on the current node.
   *
   * @return bool
   *   TRUE if the number of registered users are less than available slots,
   *   FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  private function checkSlotAvailability() {
    // Get the node object.
    $node = $this->getContextValue('node');
    // Only proceed if the node bundle is 'covid_center' and has a value in
    // field_available_slots field.
    if ($node instanceof NodeInterface
      && ($node->bundle() === 'covid_center')
      && $node->hasField('field_available_slots')
      && !$node->get('field_available_slots')->isEmpty()
      && !$node->get('field_registered_users')->isEmpty()
    ) {
      // Get the value of available slots.
      $available_slots = $node->get('field_available_slots')->getValue()[0]['value'];

      // Get the count of registered users.
      $count = count(array_column($node->get('field_registered_users')->getValue(), 'target_id'));

      // Check if the count is less than available slots.d
      if ($count < $available_slots) {
        return TRUE;
      }
    }
    // Slots are full and no booking should be accepted.
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    // Our block should rebuild if the node changes.
    if (($node = $this->getContextValue('node'))
      && $node instanceof NodeInterface
      && ($node->bundle() === 'covid_center')
    ) {
      $user = $this->getContextValue('user');
      // Prepare the cache tags.
      $cache_tags = [
        'node:' . $node->id(),
        'user:' . $user->id(),
      ];
      return Cache::mergeTags(parent::getCacheTags(), $cache_tags);
    }

    // Otherwise, return the default tags.
    return parent::getCacheTags();
  }

}
