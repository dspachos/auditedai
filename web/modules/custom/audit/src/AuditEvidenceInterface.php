<?php

declare(strict_types=1);

namespace Drupal\audit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an audit evidence entity type.
 */
interface AuditEvidenceInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
