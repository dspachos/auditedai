<?php

namespace Drupal\vp;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a virtual patient entity type.
 */
interface VirtualPatientInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
