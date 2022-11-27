<?php

namespace Drupal\slot_booking_customizations\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class RegisterationController extends ControllerBase {

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $aliasManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->aliasManager = $container->get('path_alias.manager');
    return $instance;
  }

  /**
   * Registers a user and updates the block information.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to Drupal frontend for informing the user about registration
   *   status.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function registerUser(Request $request): AjaxResponse {
    $response = new AjaxResponse();
    $postReq = $request->headers->get('referer');

    // We want to get the actual alias of the current node.
    $node_alias = explode('/', $postReq, 4);

    // Let's load the internal path of the node in form of node/nid.
    $path = $this->aliasManager->getPathByAlias("/$node_alias[3]");
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = $this->entityTypeManager->getStorage('node')->load($matches[1]);
      if ($node instanceof NodeInterface) {
        $node->get('field_registered_users')->appendItem([
          'target_id' => $this->currentUser->id(),
        ]);
        $node->save();

        // Get the user object from current user.
        $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
        if ($user instanceof UserInterface) {
          // Update the user field_covid_center.
          $user->set('field_covid_center', $node->id());
          $user->save();
        }

        // Invalidate cache in order to reflect changes quickly.
        $cache_tags[] = 'node:' . $node->id();
        $cache_tags[] = 'user:' . $this->currentUser->id();
        $cache_tags[] = 'config:block.block.registerblock';
        Cache::invalidateTags($cache_tags);

        $elem = [
          '#markup' => $this->t('You are are now register in the vaccination center.'),
        ];

        $response->addCommand(new ReplaceCommand('#register-button-div', $elem));
        return $response;
      }
    }
    $elem = [
      '#markup' => $this->t('There was some error in registering your information, please contact site manager.'),
    ];

    $response->addCommand(new ReplaceCommand('#register-button-div', $elem));
    return $response;
  }

}
