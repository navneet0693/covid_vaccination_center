<?php

namespace Drupal\slot_book_customizations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * @inheritDoc
   */
  public function build() {
    if ($this->checkStatus()) {
      return [
        '#type' => 'submit',
        '#value' => t('Register'),
        '#button_type' => 'primary',
        '#prefix' => '<div id="edit-output">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::registerUser',
          'disable-refocus' => TRUE,
          'event' => 'change',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Verifying entry...'),
          ],
        ],
      ];
    }
    else {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $this->currentRouteMatch->getRouteObject();
      return [
        '#markup' => $this->t('You are already registered in @node_title', [
          '@node_title' => $node->getTitle(),
        ]),
      ];
    }
  }

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
    $instance->database = $container->get('database');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
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
   */
  private function checkStatus(): bool {
    // Check if the current user is authenticated.
    if ($this->currentUser->isAuthenticated()) {
      // Get the user object from current user.
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      // Proceed only if we get the genuine user entity.
      if ($user instanceof UserInterface) {
        // Get the current route object.
        $node = $this->currentRouteMatch->getRouteObject();
        // Check if the route object is a valid Node object.
        // Only proceed if the node bundle is 'covid_center' and
        // current users has the registered.
        if ($node instanceof NodeInterface
          && ($node->bundle() === 'covid_center')
          && !$user->get('field_covid_center')->isEmpty()
        ) {
          // Match the node ID of in user entity's field_covid_center
          // and current node objects ID.
          // If they match, it means the current user is registered on the same
          // vaccination center.
          $value = $user->get('field_covid_center')->getValue()[0]['target_id'];
          if (!empty($value[0]['target_id'])
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

  /**
   * Registers a user and updates the block information.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function registerUser(): AjaxResponse {
    $response = new AjaxResponse();
    $node = $this->currentRouteMatch->getRouteObject();
    if ($node instanceof NodeInterface) {
      $node->get('field_registered_users')->appendItem([
        'target_id' => $this->currentUser->id(),
      ]);

      // Get the user object from current user.
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if ($user instanceof UserInterface) {
        // Update the user field_covid_center.
        $user->set('field_covid_center', $node->id());
      }
    }

    // Invalidate cache.
    $cache_tags[] = 'node:' . $node->id();
    $cache_tags[] = 'user:' . $this->currentUser->id();
    Cache::invalidateTags($cache_tags);

    $response->addCommand(new MessageCommand(
      $this->t('You are are now register in the vaccination center.'),
      NULL,
      ['type' => 'status']
    ));

    $elem = [
      '#markup' => $this->t('You are registered in @node_title', [
        '@node_title' => $node->getTitle(),
      ]),
    ];

    $response->addCommand(new ReplaceCommand('#edit-output', $elem));
    return $response;
  }

}
