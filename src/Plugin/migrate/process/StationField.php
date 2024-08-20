<?php

namespace Drupal\migrate_examples\Plugin\migrate\process;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maps D7 text field values to Drupal 10 text field values.
 * @code
 * process:
 *   field_station_field:
 *     plugin: station_field
 *     source: field_d7_field
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "station_field"
 * )
 */
class StationField extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $station_name = isset($value[0]) ? $value[0] : NULL;
    if($station_name == NULL) {
      return NULL;
    }
    $db = \Drupal\Core\Database\Database::getConnection('migrate', 'migrate');
    $query = $db->select('field_data_field_station', 'n');
    $query->fields('n', ['entity_id']);
    $query->condition('n.field_station_value', $station_name, '=');
    $query->condition('n.bundle', 'station_station', '=');
    $query->orderBy('n.entity_id', 'ASC');
    $query->range(0, 1);
    $result = $query->execute()->fetchAssoc();
    if($result == FALSE) {
      return NULL;
    }
    $nid = $result['entity_id'];
    $query = $db->select('field_data_' . $destination_property, 'f');
    $query->fields('f', [$destination_property .'_value']);
    $query->condition('f.entity_id', $nid, '=');
    $results = $query->execute()->fetchAssoc();
    if($results == FALSE) {
      return NULL;
    }
    $field_value = $results[$destination_property . '_value'];
    return $field_value;
  }
}
