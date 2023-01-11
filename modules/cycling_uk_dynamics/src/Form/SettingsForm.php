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
    return 'dynamics_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dynamics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['instance_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance URI'),
      '#default_value' => $this->config('dynamics.settings')->get('instance_uri'),
    ];
    $form['application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $this->config('dynamics.settings')->get('application_id'),
    ];
    $form['application_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application secret'),
      '#default_value' => $this->config('dynamics.settings')->get('application_secret'),
    ];

    // @todo can we calculate this rather than setting it?
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t("Used to execute custom queries the library we're using can't build"),
      '#default_value' => $this->config('dynamics.settings')->get('endpoint'),
    ];

    $form['entity_whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dynamics entity whitelist'),
      '#description' => $this->t('Dynamics entities we can integrate with, one per line. If left empty, all will be returned.'),
      '#default_value' => implode(PHP_EOL, $this->config('dynamics.settings')->get('entity_whitelist') ?? []),
    ];

    // Settings for auto qualification of applicant.
    $form['automatic_qualification'] = array(
      '#type' => 'details',
      '#title' => $this->t('Automatic Qualification'),
      '#open' => TRUE,
    );
    $form['automatic_qualification']['auto_qualified_success_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Success Threshold'),
      '#description' => $this->t('Once an applicant meets this percentage of the below "Webform Questions", they will automatically be qualified.'),
      '#default_value' => $this->config('dynamics.settings')->get('auto_qualified_success_threshold'),
    );
    $form['automatic_qualification']['auto_qualified_questions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform Questions'),
      '#description' => $this->t('Radio button questions, from the "CFP accreditation application" webform.'),
      '#default_value' => implode(PHP_EOL, $this->config('dynamics.settings')->get('auto_qualified_questions') ?? []),
      '#rows' => 10,
    ];
    $form['automatic_qualification']['auto_qualified_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $this->config('dynamics.settings')->get('auto_qualified_enable'),
    );

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
    // application_id must be in the form 6d05b2be-24c0-4eac-90b1-f72b78dd6d1a.
    if (!self::pregMatch('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/', $form_state->getValue('application_id'))) {
      $form_state->setErrorByName('application_id', $this->t('Application ID must be a valid GUID in the form abcdef01-2345-6789-abcd-ef0123456789.'));
    }
    // application_secret must exist.
    if (empty($form_state->getValue('application_secret'))) {
      $form_state->setErrorByName('application_secret', $this->t('Application secret is a required field.'));
    }
    // Endpoint must be valid URL.
    if (!UrlHelper::isValid($form_state->getValue('endpoint'), TRUE)) {
      $form_state->setErrorByName('endpoint', $this->t('Endpoint must be a valid URI.'));
    }

    // Enable the process of not.
    $auto_qualified_enable = (bool) $form_state->getValue('auto_qualified_enable');

    if ($auto_qualified_enable) {
      $success_threshold = (int) $form_state->getValue('auto_qualified_success_threshold');
      $total_questions = count(explode(PHP_EOL, $form_state->getValue('auto_qualified_questions')));

      // Validate that at least one question has been defined.
      if ($total_questions < 1) {
        $form_state->setErrorByName('auto_qualified_questions', $this->t('There must be at least 1 Webform Questions defined.'));
      }
      // Validate success_threshold.
      if ($success_threshold > 100 || $success_threshold < 1) {
        $form_state->setErrorByName('auto_qualified_success_threshold', $this->t('The success threshold must be a number between 1 and 100.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dynamics.settings');
    $config
      ->set('instance_uri', $form_state->getValue('instance_uri'))
      ->set('application_id', $form_state->getValue('application_id'))
      ->set('application_secret', $form_state->getValue('application_secret'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('entity_whitelist', preg_split("/\r\n|\n|\r/", $form_state->getValue('entity_whitelist'), -1, \PREG_SPLIT_NO_EMPTY));

    $config->set('auto_qualified_enable', $form_state->getValue('auto_qualified_enable'));

    if ($form_state->getValue('auto_qualified_enable')) {
      $config->set('auto_qualified_success_threshold', $form_state->getValue('auto_qualified_success_threshold'));
      $config->set('auto_qualified_questions', preg_split("/\r\n|\n|\r/", $form_state->getValue('auto_qualified_questions'), -1, \PREG_SPLIT_NO_EMPTY));
    }

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
