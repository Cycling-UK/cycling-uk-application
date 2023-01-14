<?php

namespace Drupal\cycling_uk_application\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission dynamics_confirmation handler.
 *
 * @WebformHandler(
 *   id = "dynamics_confirmation",
 *   label = @Translation("Dynamics: Confirmation"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Cycling Confirmation webform submission handler behaviors."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class ConfirmationWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    // Get the "dynamics_confirmation" element name for getting data.
    $elements = $webform_submission
      ->getWebform()
      ->getElementsInitialized();

    $types = [
      'radios',
      'dynamics_question',
    ];

    $count = 0;
    $count_yes = 0;
    $data = $webform_submission->getData();

    // Get answers from Cycling Question elements.
    $questions_data = $this->getQuestionsData($data, $elements);

    if (!empty($data)) {
      foreach ($elements as $element) {
        if (isset($element['#webform_plugin_id'])
          && $element['#webform_plugin_id'] === 'dynamics_confirmation') {
          $plugin_name = $element['#webform_key'];
        }
        else {
          foreach ($element as $element_item) {
            if (is_array($element_item)) {
              if (isset($element_item['#webform_plugin_id'])
                && $element_item['#webform_plugin_id'] === 'dynamics_confirmation') {
                $plugin_name = $element_item['#webform_key'];
              }

              // Get count of the optional questions,
              // and count of them with answer Yes.
              if (isset($element_item['#type'])
                && in_array($element_item['#type'], $types)
                && !isset($element_item['#required'])
                && !isset($element_item['#question_required'])) {
                $count++;

                if (isset($questions_data[$element_item['#webform_key']])
                  && $questions_data[$element_item['#webform_key']] === 'yes'
                  || $data[$element_item['#webform_key']] === 'Answer: yes') {
                  $count_yes++;
                }
              }
            }
          }
        }
      }

      if (isset($plugin_name) && !empty($data[$plugin_name])) {
        // phpcs:ignore
        $plugin_values = @unserialize($data[$plugin_name]);

        // Calculate and set statistics of the "Yes" values.
        if ($count) {
          $percent = round((100 / $count) * $count_yes, 2);
          $statistic = '"Yes" results: ' . $count_yes . ' of ' . $count . '<br /> Percentage: ' . $percent . '%';
          $plugin_values['statistic'] = $statistic;
          $data[$plugin_name] = serialize($plugin_values);
          $webform_submission->setData($data);
        }

        // Set Draft or Confirmed status for submission. Chose by Admin.
        if (!empty($plugin_values['confirmation_status'])) {
          $status = $plugin_values['confirmation_status']['value'];

          if ($status === 'draft' && !$webform_submission->isDraft()) {
            $webform_submission->set('in_draft', TRUE);
          }
          elseif ($status === 'confirmed' && $webform_submission->isDraft()) {
            $webform_submission->set('in_draft', FALSE);
          }
        }
      }
    }
  }

  /**
   * Return answers from Cycling Question elements.
   *
   * @param array $webform_data
   *   Webform submission values.
   * @param array $elements
   *   List of the webform elements.
   *
   * @return array
   *   Questions data.
   */
  protected function getQuestionsData(array $webform_data, array $elements) {
    $data = [];

    foreach ($elements as $element) {
      if (
        isset($element['#webform_plugin_id'])
        && $element['#webform_plugin_id'] === 'dynamics_confirmation'
      ) {
        $plugin_name = $element['#webform_key'];

        if (isset($webform_data[$plugin_name])) {
          // phpcs:ignore
          $data[$plugin_name] = @unserialize($webform_data[$plugin_name]);
        }
      }
      else {
        foreach ($element as $element_item) {
          if (is_array($element_item)) {
            if (
              isset($element_item['#webform_plugin_id'])
              && $element_item['#webform_plugin_id'] === 'dynamics_question'
            ) {
              $plugin_name = $element_item['#webform_key'];

              if (!empty($webform_data[$plugin_name])) {
                // phpcs:ignore
                $values = @unserialize($webform_data[$plugin_name]);
                $data[$plugin_name] = $values['question']['value'] ?? '';
              }
            }
          }
        }
      }
    }

    return $data;
  }

}
