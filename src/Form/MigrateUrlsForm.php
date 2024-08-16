<?php

namespace Drupal\migrate_examples\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * CPB File Path Import Form.
 */
class MigrateUrlsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['migrate_examples.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_examples_migrate_urls_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    // If the values are already set, get them.
    $config = $this->config('migrate_examples.settings');
    
    // Create url field to save api endpoint.
    $form['migrate_station_licensees_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Enter API Endpoint for Licensees'),
      '#default_value' => $this->config('migrate_examples.settings')->get('migrate_station_licensees_endpoint'),
      '#required' => TRUE,
    ];

    $form['migrate_station_grantees_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Enter API Endpoint for Grantees'),
      '#default_value' => $this->config('migrate_examples.settings')->get('migrate_station_grantees_endpoint'),
      '#required' => TRUE,
    ];

    $form['migrate_station_transmitters_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Enter API Endpoint for Transmitters'),
      '#default_value' => $this->config('migrate_examples.settings')->get('migrate_station_transmitters_endpoint'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('migrate_examples.settings')
      ->set('migrate_station_licensees_endpoint', $values['migrate_station_licensees_endpoint'])
      ->set('migrate_station_grantees_endpoint', $values['migrate_station_grantees_endpoint'])
      ->set('migrate_station_transmitters_endpoint', $values['migrate_station_transmitters_endpoint'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}
