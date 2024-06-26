<?php

namespace Drupal\cycling_uk_dynamics\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Dynamics settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cycling_uk_dynamics.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cycling_uk_dynamics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['env'] = [
      '#type' => 'select',
      '#name' => 'env',
      '#title' => 'Environment',
      '#options' => ['dev' => 'Development', 'prod' => 'Live'],
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('env'),
    ];
    $form['application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('application_id'),
    ];

    $form['application_prod_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Live settings'),
    ];
    $form['application_prod_wrapper']['instance_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance URI'),
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('instance_uri'),
    ];

    // @todo can we calculate this rather than setting it?
    $form['application_prod_wrapper']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t("Used to execute custom queries the library we're using can't build"),
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('endpoint'),
    ];

    $form['application_dev_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Dev settings'),
    ];
    $form['application_dev_wrapper']['endpoint_dev'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint for Dev'),
      '#description' => $this->t("Used to execute custom queries the library we're using can't build"),
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('endpoint_dev'),
    ];
    $form['application_dev_wrapper']['instance_uri_dev'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance URI for Dev'),
      '#default_value' => $this->config('cycling_uk_dynamics.settings')->get('instance_uri_dev'),
    ];

    $form['entity_whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dynamics entity whitelist'),
      '#description' => $this->t('Dynamics entities we can integrate with, one per line. If left empty, all will be returned.'),
      '#default_value' => implode(PHP_EOL, $this->config('cycling_uk_dynamics.settings')->get('entity_whitelist') ?? []),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // instance_uri must be valid URL.
    if (!UrlHelper::isValid($form_state->getValue('instance_uri'), TRUE)) {
      $form_state->setErrorByName('instance_uri', $this->t('Instance URI must be a valid URI.'));
    }
    // instance_uri must be valid URL.
    if (!UrlHelper::isValid($form_state->getValue('instance_uri_dev'), TRUE)) {
      $form_state->setErrorByName('instance_uri_dev', $this->t('Instance URI for dev must be a valid URI.'));
    }
    // application_id must be in the form 6d05b2be-24c0-4eac-90b1-f72b78dd6d1a.
    if (!self::pregMatch('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/', $form_state->getValue('application_id'))) {
      $form_state->setErrorByName('application_id', $this->t('Application ID must be a valid GUID in the form abcdef01-2345-6789-abcd-ef0123456789.'));
    }

    // Endpoint must be valid URL.
    if (!UrlHelper::isValid($form_state->getValue('endpoint'), TRUE)) {
      $form_state->setErrorByName('endpoint', $this->t('Endpoint must be a valid URI.'));
    }
    // Endpoint must be valid URL.
    if (!UrlHelper::isValid($form_state->getValue('endpoint_dev'), TRUE)) {
      $form_state->setErrorByName('endpoint_dev', $this->t('Endpoint for dev must be a valid URI.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cycling_uk_dynamics.settings');
    $config
      ->set('instance_uri', $form_state->getValue('instance_uri'))
      ->set('env', $form_state->getValue('env'))
      ->set('instance_uri_dev', $form_state->getValue('instance_uri_dev'))
      ->set('application_id', $form_state->getValue('application_id'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('endpoint_dev', $form_state->getValue('endpoint_dev'))
      ->set('entity_whitelist', preg_split("/\r\n|\n|\r/", $form_state->getValue('entity_whitelist'), -1, \PREG_SPLIT_NO_EMPTY));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * A local wrapper for preg_match().
   *
   * Throws an InvalidArgumentException if there is an internal error in the
   * PCRE engine. This avoids us needing to check for "false" every time PCRE
   * is used.
   *
   * @throws \InvalidArgumentException
   *   On a PCRE internal error.
   *
   * @see preg_last_error()
   *
   * @internal
   */
  protected static function pregMatch(
    string $pattern,
    string $subject,
    array &$matches = NULL,
    int $flags = 0,
    int $offset = 0
  ): int {
    if (FALSE === $ret = preg_match($pattern, $subject, $matches, $flags, $offset)) {
      // @todo replace with call to preg_last_error_msg() when we hit PHP 8.
      switch (preg_last_error()) {
        case \PREG_INTERNAL_ERROR:
          $error = 'Internal PCRE error.';
          break;

        case \PREG_BACKTRACK_LIMIT_ERROR:
          $error = 'pcre.backtrack_limit reached.';
          break;

        case \PREG_RECURSION_LIMIT_ERROR:
          $error = 'pcre.recursion_limit reached.';
          break;

        case \PREG_BAD_UTF8_ERROR:
          $error = 'Malformed UTF-8 data.';
          break;

        case \PREG_BAD_UTF8_OFFSET_ERROR:
          $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point.';
          break;

        default:
          $error = 'Error.';
      }

      throw new \InvalidArgumentException($error);
    }

    return $ret;
  }

}
