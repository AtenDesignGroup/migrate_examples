<?php

namespace Drupal\panda_importer\Plugin\migrate\process;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maps D7 location values to D8 address values.
 * @code
 * process:
 *   field_panda_panda_field:
 *     plugin: panda_field
 *     source: field_d7_field
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "panda_field"
 * )
 */
class PandaField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $panda_id = isset($value[0]) ? $value[0] : NULL;
    if($panda_id == NULL) {
      return NULL;
    }
    $db = \Drupal\Core\Database\Database::getConnection('migrate', 'migrate');

    $query = $db->select('field_data_field_panda', 'n');
    $query->fields('n', ['entity_id']);
    $query->condition('n.field_panda_value', $panda_id, '=');
    $query->condition('n.bundle', 'panda_panda', '=');
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
