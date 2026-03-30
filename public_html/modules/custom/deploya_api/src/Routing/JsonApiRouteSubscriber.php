<?php

declare(strict_types=1);

namespace Drupal\deploya_api\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Restricts all JSON:API routes to users with the Deploya API permission.
 *
 * Every route flagged with the _is_jsonapi option receives an additional
 * _permission requirement. The existing JSON:API access checks (entity-level
 * CRUD, field-level access, etc.) continue to apply on top of this gate.
 */
class JsonApiRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($collection->all() as $route) {
      $defaults = $route->getDefaults();
      if (!empty($defaults['_is_jsonapi'])) {
        $route->setRequirement('_permission', 'access deploya json api');
      }
    }
  }

}
