<?php

namespace Drupal\vp_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vp\Entity\VirtualPatient;

/**
 * Returns scores.
 */
class VpScores extends ControllerBase {

  private const PAGE_LIMIT = 25;

  use StringTranslationTrait;

  /**
   * Builds the response.
   */
  public function build(?VirtualPatient $virtual_patient = NULL) {

    $scores = $this->entityTypeManager()->getStorage('vp_analytics')->loadByProperties([
      'field_virtual_patient' => $virtual_patient->id(),
    ]);

    usort($scores, function ($a, $b) {
      return $a->field_score->value > $b->field_score->value ? -1 : 1;
    });

    $pager = \Drupal::service('pager.manager')->createPager(count($scores), self::PAGE_LIMIT);
    $currentPage = $pager->getCurrentPage();

    $header = [
      'date' => $this->t('Date / Time'),
      'email' => $this->t('Email'),
      'score' => $this->t('Score'),
      'time' => $this->t('Time (seconds)'),
    ];

    $scores = array_slice($scores, $currentPage * self::PAGE_LIMIT, self::PAGE_LIMIT);
    $output = [];
    foreach ($scores as $score) {

      $date = DrupalDateTime::createFromTimestamp($score->created->value);
      $output[] = [
        'date' => $date->format('H:i d.m.Y'),
        'email' => $score->field_email->value,
        'score' => $score->field_score->value ?: '-',
        'time' => $score->field_playtime->value ?: '-',
      ];
    }

    $build['tooltips'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => $this->t('No scores found'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build ?? [];
  }

  /**
   * Route title callback.
   */
  public function getTitle(?VirtualPatient $virtual_patient = NULL) {
    return $virtual_patient ? "Scores: " . $virtual_patient->label() : '';
  }

}
