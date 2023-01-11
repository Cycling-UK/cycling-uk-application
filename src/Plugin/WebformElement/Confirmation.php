<?php

namespace Drupal\cycling_uk_application\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cycling_uk_application\WebFormTrait;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'dynamics_confirmation' element.
 *
 * @WebformElement(
 *   id = "dynamics_confirmation",
 *   label = @Translation("Dynamics: Confirmation"),
 *   description = @Translation("Provides a webform composite element."),
 *   category = @Translation("Composite elements"),
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 */
class Confirmation extends WebformElementBase {

  use WebFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::defineDefaultProperties() + [
      // Element settings.
      'title' => '',
      'required' => FALSE,
      'default_value' => '',
      // Confirmation section.
      'roles' => [],
      'need_further_evidence' => '',
      'confirmation_status' => '',
      'statistic' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $values = $form_state->get('element_properties');

    $roles_options = [];
    $roles = $this
      ->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();

    foreach ($roles as $key => $role) {
      $roles_options[$key] = $role->label();
    }

    $confirmation_status_options = [
      'draft' => $this->t('Draft'),
      'confirmed' => $this->t('Confirmed'),
    ];

    $form['element']['confirmation'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation'),
      '#open' => TRUE,
    ];

    $form['element']['confirmation']['roles'] = [
      '#title' => $this->t('Roles available'),
      '#type' => 'checkboxes',
      '#options' => $roles_options,
      '#default_value' => $values['roles'] ?? [],
      '#multiple' => TRUE,
      '#description' => $this->t(
        'User roles for which fields will be available.'
      ),
    ];

    $form['element']['confirmation']['need_further_evidence'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Need further evidence'),
      '#default_value' => $values['need_further_evidence'] ?? '',
    ];

    $form['element']['confirmation']['confirmation_status'] = [
      '#title' => $this->t('Confirmation Status'),
      '#type' => 'select',
      '#options' => $confirmation_status_options,
      '#empty_option' => $this->t('- Select status -'),
      '#default_value' => $values['confirmation_status'] ?? '',
    ];

    $form['element']['confirmation']['statistic'] = [
      '#type' => 'hidden',
      '#default_value' => $values['statistic'] ?? '',
    ];

    return $form;
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build(
    $format,
    array &$element,
    WebformSubmissionInterface $webform_submission,
    array $options = []
  ) {
    if ($this->getUserAccess($element['#roles'])) {
      $options['multiline'] = $this->isMultiline($element);
      $format_function = 'format' . ucfirst($format);

      $elementValue = $this->$format_function(
        $element,
        $webform_submission,
        $options
      );

      // Handle empty value or empty array.
      if (
        $elementValue === ''
        || (
          is_array($elementValue)
          && $elementValue === []
        )
      ) {
        // Return NULL if empty is excluded.
        if ($this->isEmptyExcluded($element, $options)) {
          return NULL;
        }
        // Else set the formatted value to empty message/placeholder.
        else {
          $elementValue = $this->configFactory->get('webform.settings')
            ->get('element.empty_message');
        }
      }

      $items = [];
      if (!empty($elementValue)) {
        if (is_array($elementValue)) {
          // phpcs:ignore
          $values = @unserialize(reset($elementValue));
        }
        else {
          // phpcs:ignore
          $values = @unserialize($elementValue);
        }

        $comparedValue = '';
        foreach ($values as $key => $valueItem) {
          // Convert string to renderable #markup.
          if (is_string($valueItem)) {
            $value = $valueItem;
            $title = ucfirst($key);
          }
          else {
            $value = $valueItem['value'];
            $title = $valueItem['title'];
          }

          if (!empty($value)) {
            if ($format === 'html') {
              $attributes = $this->setItemAttributes($element);

              $item = [
                '#type' => 'item',
                '#title' => $title,
                '#name' => $element['#webform_key'],
                '#wrapper_attributes' => $attributes,
              ];
              if (is_array($value)) {
                $item['value'] = $value;
              }
              else {
                $item['#markup'] = $value;
              }

              $items[] = $item;
            }
            else {
              $comparedValue .= $title . ': ' . $value . '; ';
            }
          }
        }
      }

      return [
        '#theme' => 'webform_element_dynamics_' . $format,
        '#element' => $element,
        '#items' => $items,
        '#title' => $element['#title'] ?? '',
        '#value' => $comparedValue ?? $value,
        '#webform_submission' => $webform_submission,
        '#options' => $options,
      ];
    }

    return [];
  }

}
