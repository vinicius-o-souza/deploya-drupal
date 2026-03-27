<?php

declare(strict_types=1);

namespace Drupal\deploya_api\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;
use Drupal\user\UserInterface;

/**
 * Hooks for Simple OAuth integration.
 */
class OAuthHooks {

  /**
   * Assigns the 'pokedex' role when a user authorizes the app via OAuth.
   *
   * Only auth_code tokens are checked: they are created at the moment the
   * user clicks "Authorize" on the consent screen, making them the definitive
   * signal that a real user went through the app's OAuth PKCE flow.
   * Admin-created accounts and direct Drupal registrations never produce an
   * auth_code, so they are unaffected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity to be inserted.
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    if (!$entity instanceof Oauth2TokenInterface) {
      return;
    }

    if ($entity->bundle() !== 'auth_code') {
      return;
    }

    $user = $entity->get('auth_user_id')->entity;
    if (!$user instanceof UserInterface) {
      return;
    }

    if (!in_array('pokedex', $entity->getRoles(), TRUE)) {
      return;
    }

    if ($user->hasRole('pokedex')) {
      return;
    }

    $user->addRole('pokedex');
    $user->save();
  }

}
