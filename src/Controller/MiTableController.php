<?php

namespace Drupal\migrate_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateException;
use Drupal\block\Entity\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate_examples\StationService;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpClient\HttpClient;


class MiTableController extends ControllerBase {


    /**
     * Proof of Concept for Import stationss from the API.
     * @return array
     */
    public function importstationss() {

      $uri = 'https://abc-stations.net/stations';

      $migration_id = 'stations_import';
          // Get configuration from ImporForm.
      $config = \Drupal::config('migrate_examples.settings');
      $import_data = $config->get('stationss_api_endpoint');
        
      // if grantees is null return to page with message to configure api url.
      if($import_data == null) {
        $message = $this->t('Please configure the API endpoint.');
        \Drupal::messenger()->addMessage($message);
        // Return to the previous page.
        $request = \Drupal::request();
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
      }

      // Create the search query form.
      $build['form'] =\Drupal::formBuilder()->getForm('Drupal\migrate_examples\Form\SearchTableForm');
      $search_query = \Drupal::request()->query->get('search');
      $search_rows = [];
      if($search_query) {
        $search = array_filter($search, function ($row) use ($search_query) {
          $columns_to_search = ['title'];
          foreach ($columns_to_search as $column) {
            if (strpos($row[$column], $search_query) !== FALSE) {
              return [
                'id' => $row['id'],
              ];
            }
          }
          return 0;
        });

        if($search !== 0) {  
          // Get the first key from array.
          $search = reset($search);
          $id = $search['id'];
          foreach($rows as $row) {
            if($id == $row['id']) {
              $search_rows[] = $row;
            }
          }
        }
      }

      if(count($search_rows) > 0) {
        $rows = $search_rows;
      }

      $header_row = [
        'ID',
        'Title',
      ];

      if(count($import_data) < 1) {
        $build['table'] = [
          '#markup' => $this->t('No data found.'),
        ];
        return $build;
      }  

      // Create table with data.
      $build['table'] = $this->createTable($import_data, $migration_id, $header_row);
      // Create the import all button.
      $build['import_all'] = $this->createMigrationButton('stations_import', 'migrate_examples.import_migration', 'Import All');
      // Pager was added with createTable function.
      $build['pager'] = [
        '#type' => 'pager',
      ];
      
      return $build;

    }

    public function createTable($rows, $migration_id, $header_row) {
      $total = count($rows);
      $limit = 10;
      $build[] = [
        '#theme' => 'stationss_table__header',
      ];
      $data = '';
      $pager_manager = \Drupal::service('pager.manager');
      $current_page = $pager_manager->createPager($total, $limit)->getCurrentPage();
      $data = array_slice($rows, $current_page * $limit, $limit);
      $pager_manager->createPager($total, $limit);
      $table = [
        '#type' => 'table',
        '#header' =>  $header_row,
        '#rows' => $data,
        '#caption' => '',
      ];
      return $table;
    }

    public function createMigrationButton($migration_id, $route_name, $button_text) {
      $url = Url::fromRoute($route_name, ['migration_id' => $migration_id]);
      $link = Link::fromTextAndUrl($this->t($button_text), $url)->toRenderable();
      $link['#attributes'] = ['class' => ['button']];
      return $link;
    }

    public function createMigrationByIdButton($migration_id, $lid, $route_name, $button_text) {
      $url = Url::fromRoute('migrate_examples.import_row', ['row_id' => $lid, 'migration_id' => $migration_id]);
      $link = Link::fromTextAndUrl($this->t('Import'), $url)->toRenderable();
      $link['#attributes'] = ['class' => ['button']];
      return $link;
    }

}
