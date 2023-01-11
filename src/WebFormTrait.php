<?php

namespace Drupal\cycling_uk_application;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "WebFormTrait" trait class.
 */
trait WebFormTrait {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationFormProperty(
    array &$properties,
    $property_name,
    $property_value,
    array $element
  ): void {
    if ($property_name === 'help_text') {
      $properties['help_text'] = $property_value['value'];
      $properties['help_format'] = $property_value['format'];
    }
    else {
      parent::getConfigurationFormProperty(
        $properties,
        $property_name,
        $property_value,
        $element
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserAccess($element_roles): bool {
    $roles = [];

    foreach ($element_roles as $role) {
      if ($role !== 0) {
        $roles[] = $role;
      }
    }

    $user_roles = $this->currentUser->getRoles();
    $access_roles = array_intersect($user_roles, $roles);

    if (!empty($access_roles)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getValueCallback(&$element, $input, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $composite_elements = static::getCompositeElements($element);
    $composite_elements = WebformElementHelper::getFlattened($composite_elements);

    // Get default value for inputs.
    $default_value = [];
    if (!empty($composite_elements)) {
      foreach ($composite_elements as $composite_key => $composite_element) {
        $element_plugin = $element_manager->getElementInstance($composite_element);
        if ($element_plugin->isInput($composite_element)) {
          $default_value[$composite_key] = '';
        }
      }

      if (
        $input === FALSE
        && isset($element['#default_value'])
        && (
          empty($element['#default_value'])
          || !is_array($element['#default_value'])
        )
      ) {
        // phpcs:ignore
        $default_values = @unserialize($element['#default_value']);

        if (!empty($default_values)) {
          foreach ($default_values as &$value) {
            if (is_array($value)) {
              $value = $value['value'];
            }
          }
        }

        $element['#default_value'] = $default_values;

        return $element['#default_value'];
      }
    }

    return is_array($input)
      ? $input + $default_value
      : $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function setItemAttributes($element) {
    $attributes = $element['#format_attributes'] ?? [];
    $attributes += ['class' => []];
    // Use wrapper attributes for the id instead of #id,
    // this stops the <label> from having a 'for' attribute.
    $attributes += [
      'id' => $element['#webform_id'],
    ];
    $attributes['class'][] = 'webform-element';
    $attributes['class'][] = 'webform-element-type-' . str_replace('_', '-', $element['#type']);

    return $attributes;
  }

}
