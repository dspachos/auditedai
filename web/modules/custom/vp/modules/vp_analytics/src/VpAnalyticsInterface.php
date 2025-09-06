<?php

namespace Drupal\vp_analytics;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a vp analytics entity type.
 */
interface VpAnalyticsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
