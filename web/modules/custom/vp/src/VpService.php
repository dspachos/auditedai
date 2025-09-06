<?php

namespace Drupal\vp;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service description.
 */
class VpService
{

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $account;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The current route match.
     *
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected $routeMatch;

    /**
     * Constructs a VpService object.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The current user.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The current route match.
     */
    public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match)
    {
        $this->account = $account;
        $this->entityTypeManager = $entity_type_manager;
        $this->routeMatch = $route_match;
    }

    /**
     * Clear root nodes for a given Virtual Patient.
     *
     * @param \Drupal\vp\Entity\VirtualPatient $vp_patient
     */
    public function clearRootNodes(EntityInterface $vp_patient)
    {
        /**
         * @var \Drupal\vp\Entity\VirtualPatient $vp_patient
         */
        $nodes = $vp_patient->field_vp_nodes->referencedEntities();
        foreach ($nodes as $node) {
            /**
             * @var \Drupal\vp\Entity\VirtualPatientNode $node
             */
            if ($node->field_root_node->value == TRUE) {
                $node->set('field_root_node', FALSE);
                $node->save();
            }
        }
    }

    /**
     * Set a node as the root node for a Virtual Patient.
     *
     * @param \Drupal\vp\Entity\VirtualPatientNode $vp_node
     */
    public function setRootNode(EntityInterface $vp_node)
    {
        /**
         * @var \Drupal\vp\Entity\VirtualPatientNode $vp_node
         */
        $vp_node->set('field_root_node', TRUE);
        $vp_node->save();
    }

    /**
     * Get the root node for a given Virtual Patient.
     *
     * @param \Drupal\vp\Entity\VirtualPatient $vp_patient
     *
     * @return \Drupal\vp\Entity\VirtualPatientNode|null
     */
    public function getRootNode(EntityInterface $vp_patient)
    {
        /**
         * @var \Drupal\vp\Entity\VirtualPatient $vp_patient
         */
        $nodes = $vp_patient->field_vp_nodes->referencedEntities();
        foreach ($nodes as $node) {
            /**
             * @var \Drupal\vp\Entity\VirtualPatientNode $node
             */
            if ($node->field_root_node->value == TRUE) {
                return $node;
            }
        }
        return NULL;
    }

    /**
     * Get the terminal node for a given Virtual Patient.
     *
     * @param \Drupal\vp\Entity\VirtualPatientNode $vp_patient
     *
     * @return \Drupal\vp\Entity\VirtualPatientNode|null
     */
    public function getTerminalNode(EntityInterface $vp_patient)
    {
        /**
         * @var \Drupal\vp\Entity\VirtualPatientNode $vp_patient
         */
        $nodes = $vp_patient->field_vp_nodes->referencedEntities();
        foreach ($nodes as $node) {
            /**
             * @var \Drupal\vp\Entity\VirtualPatientNode $node
             */
            if ($node->field_terminal_node->value == TRUE) {
                return $node;
            }
        }
        return NULL;
    }

    /**
     * Get analytics data for a specific user account.
     *
     * @param \Drupal\user\Entity\User $account
     *
     * @return array
     */
    public function getUserAnalyticsData(AccountInterface $account)
    {
        $storage = $this->entityTypeManager->getStorage('vp_analytics');
        $data = $storage->loadByProperties(['field_user' => $account->id()]);
        $analytics = [];
        /**
         * @var \Drupal\vp\Entity\VirtualPatientNode $vp
         */
        foreach ($data as $vp) {
            $analytics[$vp->id()] = $vp->uuid->value;
        }
        return $analytics;
    }

    /**
     * Get the target language for translation.
     *
     * @return string
     */
    public static function getContentLangcode()
    {
        $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        if ($language) {
            return $language;
        }

        $content_langcode = \Drupal::request()->query->get('language_content_entity');
        $language_current = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $language_default = \Drupal::languageManager()->getDefaultLanguage()->getId();
        if (!$content_langcode && $language_default !== $language_current) {
            $content_langcode = $language_current;
        }
        if (!$content_langcode) {
            $content_langcode = $language_default;
        }


        return $content_langcode ?? 'en';
    }
}
