<?php

/*namespace Drupal\address\Plugin\migrate\process;*/
namespace Drupal\cpb_station_import\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps D7 location values to D8 address values.
 *
 * Example:
 *
 * @code
 * process:
 *   web_address:
 *     plugin: field_cpb_website
 *     source: website
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "web_address"
 * )
 */
class WebAddress extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {


    // set $value equal to array split by comma.
    $websites = explode(',', $value);

    $addresses = [];

    if($websites !== NULL) {

      // If websites is an array loop through each value and process it.
      if(is_array($websites)) {
        foreach($websites as $key => $website) {

          if($website == "") {
            continue;
          }
           // Check to see if the string is a valid URL.
          $valid_url = filter_var($website, FILTER_VALIDATE_URL) !== FALSE;

          // See if string contains a domain name.
          $domain_name = preg_match('/^(?!.*@)[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,5}(:[0-9]{1,5})?(\/.*)?$/', $website);
          
          // If the string is a valid URL, remove the protocol and www.
          if($valid_url || ($domain_name !== 0)) {
            if (strpos($website, 'http://') === false && strpos($website, 'https://') === false) {
              $website = 'http://' . $website;
            } 
          } else {
            $website = "";
          }

          $addresses[] = [
            'uri' => $website,
            'title' => $website,
          ];
          
        }
      }
    }

    return $addresses;
  }

}
