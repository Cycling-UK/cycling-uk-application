<?php

namespace Drupal\cycling_uk_dynamics\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\cycling_uk_dynamics\Connector;

/**
 * Configure Dynamics settings for this site.
 */
class ActionsForm extends FormBase {

  /**
   * The "Dynamics" connector.
   *
   * @var \Drupal\cycling_uk_dynamics\src\Connector
   */
  public Connector $dynamicsConnector;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  public MessengerInterface $drupalMessenger;

  /**
   * Constructor.
   */
  public function __construct() {
    // phpcs:ignore
    $this->dynamicsConnector = \Drupal::service('cycling_uk_dynamics.connector');
    // phpcs:ignore
    $this->drupalMessenger = \Drupal::messenger();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamics_actions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['flush'] = [
      '#type' => 'submit',
      '#name' => 'flush',
      '#value' => $this->t('Flush query cache'),
    ];

    $form['actions']['validate'] = [
      '#type' => 'submit',
      '#name' => 'validate',
      '#value' => $this->t('Validate Dynamics configuration'),
    ];

    $form['actions']['list'] = [
      '#type' => 'submit',
      '#name' => 'list',
      '#value' => $this->t('List all Dynamics entities'),
      '#ajax' => [
        'callback' => '::buildEntityList',
        'wrapper' => 'entity-list-wrapper',
        'method' => 'replaceWith',
        'effect' => 'fade',
        'disable-refocus' => TRUE,
      ],
    ];

    $form['entitylist'] = [
      '#type' => 'textarea',
      '#prefix' => '<div id="entity-list-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    // @todo check entity_whitelist elements exist on Dynamics
    return $form;
  }

  /**
   * Build list of entities.
   */
  public function buildEntityList(array &$form, FormStateInterface $form_state) {

    $logicalNames = $this->dynamicsConnector->listEntityLogicalNames();
    $logicalNames = implode(PHP_EOL, $logicalNames);
    $form['entitylist']['#value'] = $logicalNames;

    return $form['entitylist'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'flush':
        $this->dynamicsConnector->invalidateCache();
        $this->drupalMessenger->addMessage($this->t('Cache flush complete'));
        break;

      case 'validate':
        if ($this->dynamicsConnector->testConnection()) {
          $this->drupalMessenger->addMessage($this->t('Attempt to connected to Dynamics succeeded.'));
        }
        else {
          $this->drupalMessenger->addWarning($this->t('Attempt to connect to Dynamics failed. Check recent log messages for more information.'));
          return;
        }
        break;
    }

  }

}
