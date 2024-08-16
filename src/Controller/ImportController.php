<?php

namespace Drupal\migrate_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateException;
use Drupal\block\Entity\Block;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpClient\HttpClient;

class ImportController extends ControllerBase {

    /**
     * Import csv row from import table page.
     * @var int $row_id
     * @var string $migration_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function importRow($row_id, $migration_id) {
      $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
      $migration = $migration_plugin_manager->createInstance($migration_id);
      // Redirect to the previous page.
      $request = \Drupal::request();
      $referer = $request->headers->get('referer');
      if(!$migration) {
        $message = $this->t('Migration id is invalid.');
        \Drupal::messenger()->addMessage($message);
        \Drupal::logger('pandas_import')->notice($message);
        return new RedirectResponse($referer);
      }
      $options = [
        'idlist' => $row_id
      ];
      // Prepare for migration update.
      $migration->getIdMap()->prepareUpdate();
      $executable = new MigrateExecutable($migration, new MigrateMessage(), $options);
      $import = $executable->import();
      if($import == 0) {
        $message = $this->t('No row found to import.');
        \Drupal::messenger()->addMessage($message);
        \Drupal::logger('pandas_import')->notice($message);
        return new RedirectResponse($referer);
      }
      // Create a drupal query to query table migrate_map_pandas_import
      $query = \Drupal::database()->select('migrate_map_' . $migration_id, 'm');
      $query->fields('m', ['sourceid1', 'destid1']);
      $query->condition('m.sourceid1', $row_id);
      $result = $query->execute()->fetchAll();
      if(empty($result)) {
        $message = $this->t('No row found to import.');
        \Drupal::messenger()->addMessage($message);
        \Drupal::logger('pandas_import')->notice($message);
        return new RedirectResponse($referer);
      }
      $result = reset($result);
      $destid = $result->destid1;
      $import_link = \Drupal\Core\Link::fromTextAndUrl($destid, \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $destid]));
      // Create string from link.
      $import_link = $import_link->toString();
      // If the migration was successful, return a success message.
      $message = $this->t('Imported Row @rows_updated', [
        '@rows_updated' => $import_link,
      ]);
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('pandas_import')->notice($message);
      return new RedirectResponse($referer);      
    }

    public function rollbackMigration($migration_id) {
      // Get the migration plugin manager.
      $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
      $migration = $migration_plugin_manager->createInstance($migration_id);
      // Redirect to the previous page.
      $request = \Drupal::request();
      $referer = $request->headers->get('referer');
      if(!$migration) {
        $message = $this->t('Migration id is invalid.');
        \Drupal::messenger()->addMessage($message);
        \Drupal::logger('pandas_import')->notice($message);
        return new RedirectResponse($referer);
      }
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->rollback();
      // If the migration was successful, return a success message.
      $message = $this->t('Rolled back all rows.');
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('pandas_import')->notice($message);
      return new RedirectResponse($referer);
    }

    public function importMigration($migration_id) {
      // // Get the migration plugin manager.
      $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
      $migration = $migration_plugin_manager->createInstance($migration_id);;
      // Redirect to the previous page.
      $request = \Drupal::request();
      $referer = $request->headers->get('referer');       
      if(!$migration) {
        $message = $this->t('Migration id is invalid.');
        \Drupal::messenger()->addMessage($message);
        \Drupal::logger('pandas_import')->notice($message);
        return new RedirectResponse($referer);
      }
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->import();
      // If the migration was successful, return a success message.
      $message = $this->t('Imported all rows.');
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('pandas_import')->notice($message);
      return new RedirectResponse($referer);
    }
}
