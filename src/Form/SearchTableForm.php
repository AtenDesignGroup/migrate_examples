<?php

namespace Drupal\migrate_examples\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class SearchTableForm extends FormBase {

  public function getFormId() {
    return 'search_table_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => \Drupal::request()->query->get('search'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getValue('search');
    $form_state->setRebuild(TRUE);
    $request = \Drupal::request();
    $referer = $request->headers->get('referer');
    if (!$referer) {
      $referer = '/';
    }
    $url = Url::fromUri($referer, ['query' => ['search' => $search]]);
    $response = new RedirectResponse($url->toString());
    $response->send();
    return;
  }

}