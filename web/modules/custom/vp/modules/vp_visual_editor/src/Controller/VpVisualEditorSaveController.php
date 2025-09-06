<?php

namespace Drupal\vp_visual_editor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vp\Entity\VirtualPatient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Save the visual editor structure.
 */
class VpVisualEditorSaveController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Returns Ajax Response containing the current time.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Ajax response containing html to render time.
   */
  public function handleSave(Request $request) {
    $postData = json_decode($request->getContent(), TRUE);
    // @todo Add DI
    $vp = \Drupal::request()->query->get('vp');
    if ($vp) {
      $entity = VirtualPatient::load($vp);
      if ($entity) {
        // @todo Add error handling
        $entity->set('field_visual_metadata', $request->getContent());
        $entity->save();

        // @todo Add DI
        $service = \Drupal::service('vp_visual_editor.visual_editing');
        $service->saveData($request->getContent(), $entity);

        return new JsonResponse($postData);
      }
    }
    return new JsonResponse(['error' => $this->t('There is no associated VP entity')], 500);
  }

  /**
   * Route title callback.
   */
  public function getTitle() {
    return $this->t('Save');
  }

}
