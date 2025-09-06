<?php

declare(strict_types=1);

namespace Drupal\audit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an audit question entity type.
 */
interface AuditQuestionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
