<?php

declare(strict_types=1);

namespace Drupal\deploya_api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Destroys the Drupal session after a successful OAuth authorization redirect.
 *
 * When a user authenticates via OAuth 2.0 + PKCE, Drupal creates a PHP session
 * as a side effect of the authorization flow. Since the external app (Pokedex)
 * only needs the authorization code — not an active Drupal session — the
 * session is immediately invalidated once the redirect is issued.
 */
class OAuthSessionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse', 0],
    ];
  }

  /**
   * Destroys the Drupal session when an OAuth authorization code is issued.
   *
   * Triggers only when all of the following are true:
   *  - The request targets /oauth/authorize.
   *  - The response is a redirect (3xx).
   *  - The redirect Location contains a `code` query parameter.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    if (!str_contains($request->getPathInfo(), '/oauth/authorize')) {
      return;
    }

    $response = $event->getResponse();
    if (!$response->isRedirect()) {
      return;
    }

    $location = $response->headers->get('Location', '');
    if (!$location || !str_contains($location, 'code=')) {
      return;
    }

    // The authorization code has been issued and the redirect is ready.
    // Destroy the Drupal session so the browser does not receive a session
    // cookie for the Drupal back-end.
    $session = $request->getSession();
    if ($session->isStarted()) {
      $session->invalidate();
    }
  }

}
