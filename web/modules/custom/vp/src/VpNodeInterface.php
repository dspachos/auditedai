<?php

namespace Drupal\vp;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a vp node entity type.
 */
interface VpNodeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
