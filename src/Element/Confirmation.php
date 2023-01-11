<?php

namespace Drupal\cycling_uk_application\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cycling_uk_application\WebFormTrait;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides the 'dynamics_confirmation'.
 *
 * @FormElement("dynamics_confirmation")
 */
class Confirmation extends WebformCompositeBase {

  use WebFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    unset($info['#flexbox']);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $values = [];

    if (isset($element['#default_value'])) {
      if (is_string($element['#default_value'])) {
        // phpcs:ignore
        $values = @unserialize($element['#default_value']);
      }
      else {
        $values = $element['#default_value'];
      }
    }

    $roles = [];
    foreach ($element['#roles'] as $role) {
      if ($role !== 0) {
        $roles[] = $role;
      }
    }

    // @todo use dependency injection.
    $user_roles = \Drupal::currentUser()->getRoles();
    $access_roles = array_intersect($user_roles, $roles);
    if (!empty($access_roles)) {
      $confirmationStatusOptions = [
        'draft' => t('Draft'),
        'confirmed' => t('Confirmed'),
      ];

      $elements['need_further_evidence'] = [
        '#type' => 'textfield',
        '#title' => t('Need further evidence'),
        '#default_value' => $values['need_further_evidence'] ?? '',
      ];

      $elements['confirmation_status'] = [
        '#title' => t('Confirmation Status'),
        '#type' => 'select',
        '#options' => $confirmationStatusOptions,
        '#empty_option' => t('- Select status -'),
        '#default_value' => $values['confirmation_status'] ?? '',
      ];

      $elements['statistic'] = [
        '#type' => 'markup',
        '#title' => t('Statistic'),
        '#markup' => $values['statistic'] ?? t('Statistic data is not found.'),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    self::getValueCallback($element, $input, $form_state);
  }

  /**
   * Validates a composite element.
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element_value = NestedArray::getValue(
      $form_state->getValues(),
      $element['#parents']
    );

    if ($element_value === '') {
      $form_state->setValue(current($element['#parents']), []);
    }

    parent::validateWebformComposite(
      $element,
      $form_state,
      $complete_form
    );

    $value = NestedArray::getValue(
      $form_state->getValues(),
      $element['#parents'] ?? []
    ) ?? [];

    foreach ($value as $k => $item) {
      $value[$k] = [
        'value' => $item,
        'title' => $element[$k]['#admin_title'],
      ];
    }

    $element['#value'] = serialize($value);
    $form_state->setValueForElement($element, $element['#value']);
  }

}
