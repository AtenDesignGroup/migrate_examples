<?php

namespace Drupal\migrate_examples\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

class RollbackMigrateRowForm extends FormBase {

  public function getFormId() {
    return 'rollback_migration_row';
  }

  public function buildForm(array $form, FormStateInterface $form_state, array $options = NULL) {

    // Create url field to save api endpoint.
    $form['migration_row_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the migration row name'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['migration_row_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the row id'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $row_name = $form_state->getValue('migration_row_name');
    $row_id = $form_state->getValue('migration_row_id');

    $form_state->setRebuild(TRUE);

    // use migrate service to rollback the migration row
    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $migration = $migration_plugin_manager->createInstance($row_name);

    if(!$migration) {
      $message = $this->t('Migration id is invalid.');
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('migrate_examples')->notice($message);
      return;
    }

    // Rollback the migration row
    $migration->getIdMap()->prepareUpdate();

    $options = [
      'idlist' => $row_id
    ];

    $executable = new MigrateExecutable($migration, new MigrateMessage(), $options);

    $rollback = $executable->rollback();

    // Return to the form page we were on if the rollback was successful
    $request = \Drupal::request();
    $referer = $request->headers->get('referer');

    if($rollback) {
      $message = $this->t('Rolled back row @row_id.', ['@row_id' => $row_id]);
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('migrate_examples')->notice($message);
      return new RedirectResponse($referer);
    }
  
  }

}