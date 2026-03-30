<?php

declare(strict_types=1);

namespace Drupal\deploya_api\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hooks for Simple OAuth integration.
 */
class OAuthHooks {

  use StringTranslationTrait;

  /**
   * Constructs an OAuthHooks instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_user_login().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account that has logged in.
   */
  #[Hook('user_login')]
  public function userLogin(AccountInterface $account): void {
    if ($this->hasOauthAuthorizeDestination()) {
      if (in_array('pokedex', $account->getRoles(), TRUE)) {
        return;
      }

      if ($account instanceof UserInterface) {
        $account->addRole('pokedex');
        $account->save();
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * @param array<string, mixed> $form
   *   The form structure.
   */
  #[Hook('form_user_register_form_alter')]
  public function userRegisterFormAlter(array &$form): void {
    if ($this->hasOauthAuthorizeDestination()) {
      $message = $this->t('An application is requesting access to your account. Create an account or log in to continue and grant access.');
      $this->messenger->addStatus($message);
    }
  }

  /**
   * Checks if the current request has an OAuth authorize destination.
   *
   * @return bool
   *   TRUE if the request destination contains '/oauth/authorize'.
   */
  private function hasOauthAuthorizeDestination(): bool {
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return FALSE;
    }
    $query = $request->query;
    if ($query->has('destination')) {
      $destination = $query->get('destination', '');
      if (str_contains($destination, '/oauth/authorize')) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
