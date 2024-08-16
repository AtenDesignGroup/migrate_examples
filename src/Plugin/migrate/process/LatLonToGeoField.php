<?php

namespace Drupal\migrate_examples\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maps D7 geofield values to new the geofield values.
 *
 * @MigrateProcessPlugin(
 *   id = "lat_lon_to_geofield"
 * )
 */
class LatLonToGeoField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The WktGenerator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WktGeneratorInterface $wkt_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->wktGenerator = $wkt_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geofield.wkt_generator')
    );
  }

  // Function to convert DMS to decimal degrees
  private function dms_to_decimal($degrees, $minutes, $seconds, $direction) {
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    if ($direction == 'S' || $direction == 'W') {
        $decimal *= -1; // For southern latitudes and western longitudes, make the value negative
    }
    return $decimal;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    if (empty($value)) {
        throw new MigrateException('Value is NULL');
    }
       
    // set $value equal to array split by comma.
    $value = explode(',', $value);

    $latitude_dms = $value[0];
    $longitude_dms = $value[1];

    // Extract degrees, minutes, seconds, and direction for latitude
    preg_match('/(\d+)° (\d+)\' ([\d\.]+)\'\' ([NS])/', $latitude_dms, $lat_matches);
    $lat_degrees = intval($lat_matches[1]);
    $lat_minutes = intval($lat_matches[2]);
    $lat_seconds = floatval($lat_matches[3]);
    $lat_direction = $lat_matches[4];

    // Extract degrees, minutes, seconds, and direction for longitude
    preg_match('/(\d+)° (\d+)\' ([\d\.]+)\'\' ([EW])/', $longitude_dms, $long_matches);
    $long_degrees = intval($long_matches[1]);
    $long_minutes = intval($long_matches[2]);
    $long_seconds = floatval($long_matches[3]);
    $long_direction = $long_matches[4];

    // Convert latitude and longitude to decimal degrees
    $latitude_decimal = $this->dms_to_decimal($lat_degrees, $lat_minutes, $lat_seconds, $lat_direction);
    $longitude_decimal = $this->dms_to_decimal($long_degrees, $long_minutes, $long_seconds, $long_direction);

    $value = [$latitude_decimal, $longitude_decimal];
    
    $value = array_map('floatval', $value);
    [$lat, $lon] = $value;

    if (empty($lat) && empty($lon)) {
      return NULL;
    }

    return $this->wktGenerator->WktBuildPoint([$lon, $lat]);
  }



}
