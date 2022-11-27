<?php

namespace Drupal\slot_booking_customizations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with a button.
 *
 * @Block(
 *   id = "register_block",
 *   admin_label = @Translation("Register Block")
 * )
 */
class RegisterButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
  }

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
      $node = $this->currentRouteMatch->getParameter('node');
      return [
        '#markup' => $this->t('You are already registered in @node_title', [
          '@node_title' => $node->getTitle(),
        ]),
      ];
    }
    else {
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

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
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
    // Check if the current user is authenticated.
    if ($this->currentUser->isAuthenticated()) {
      // Get the user object from current user.
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      // Proceed only if we get the genuine user entity.
      if ($user instanceof UserInterface) {
        // Get the current route object.
        $node = $this->currentRouteMatch->getParameter('node');
        // Check if the route object is a valid Node object.
        // Only proceed if the node bundle is 'covid_center' and
        // current users has the registered.
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
    }
    // Otherwise, user has not registered.
    return FALSE;
  }

}
