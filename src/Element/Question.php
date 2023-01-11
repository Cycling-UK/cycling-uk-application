<?php

namespace Drupal\cycling_uk_application\Element;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\cycling_uk_application\WebFormTrait;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides the 'dynamics_question'.
 *
 * @FormElement("dynamics_question")
 */
class Question extends WebformCompositeBase {

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

    if (!empty($element['#question_title'])) {
      $question_options = [
        'yes' => 'Yes',
        'no' => 'No',
      ];

      if (
        isset($element['#question_type'])
        && $element['#question_type'] == 'yes_no_unsure'
      ) {
        $question_options += ['unsure' => 'Unsure'];
      }

      $elements['question'] = [
        '#type' => 'radios',
        // phpcs:ignore
        '#title' => t($element['#question_title']),
        '#title_display' => 'before',
        '#required' => $element['#question_required'] ?? FALSE,
        '#required_error' => t('Question is required.'),
        '#options' => $question_options,
      ];

      $elements['sub_element'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            [
              ':input[name="' . $element['#name'] . '[question]"]' => [
                'value' => 'yes',
              ],
            ],
          ],
        ],
      ];

      if (!empty($element['#file_enable'])) {
        $allowed_ext = $element['#file_allowed_ext'] ?? 'gif png jpg jpeg';
        $max_size = $element['#file_max_upload'] ?? Environment::getUploadMaxSize();

        $max_filesize = min(
          Bytes::toNumber($max_size . 'MB'),
          Environment::getUploadMaxSize()
        );

        $elements['sub_element']['file_fid'] = [
          '#type' => 'managed_file',
          '#title' => $element['#file_title'],
          '#after_build' => [[static::class, 'afterFileBuild']],
          '#description' => t(
            'Valid extensions: @allowed_ext. Images must be exactly @size MB',
            [
              '@allowed_ext' => $allowed_ext,
              '@size' => $max_filesize,
            ]
          ),
          '#upload_location' => 'private://',
          '#multiple' => FALSE,
          '#upload_validators' => [
            'dynamics_upload_validators' => [$allowed_ext, $max_filesize],
          ],
        ];

        if (!empty($element['#file_required'])) {
          // @todo The require states don't work with upload file.
          $elements['sub_element']['file_fid']['#states']['required'] = [
            ':input[name="' . $element['#name'] . '[question]"]' => ['value' => 'yes'],
          ];

          $elements['sub_element']['file_fid']['#element_validate'] = [
            [static::class, 'uploadFileValidate'],
          ];

          $elements['sub_element']['file_fid']['#machine_name'] = $element['#name'];
          $elements['sub_element']['file_fid']['#file_required'] = $element['#file_required'];
        }
      }

      if (!empty($element['#details_enable'])) {
        if ($element['#details_type'] == 'textarea') {
          $elements['sub_element']['details'] = [
            '#type' => 'textarea',
            '#title' => $element['#details_title'],
            '#title_display' => 'before',
          ];
        }
        elseif (!empty($element['#details_options'])) {
          $element_type = isset($element['#details_option_other'])
            ? 'webform_select_other'
            : 'select';

          $elements['sub_element']['details'] = [
            '#type' => $element_type,
            '#title' => $element['#details_title'],
            '#options' => $element['#details_options'],
            '#title_display' => 'before',
          ];
        }

        if (!empty($element['#details_required'])) {
          $elements['sub_element']['details']['#states']['required'] = [
            ':input[name="' . $element['#name'] . '[question]"]' => ['value' => 'yes'],
          ];
        }
      }

      if (!empty($element['#help_text'])) {
        $elements['sub_element']['help_text'] = [
          '#type' => 'markup',
          '#markup' => $element['#help_text'],
        ];
      }

      if (
        !empty($element['#help_link_title'])
        && !empty($element['#help_link_url'])
      ) {
        $url = Url::fromUri($element['#help_link_url']);

        $elements['sub_element']['help_link'] = [
          '#type' => 'link',
          '#url' => $url,
          '#title' => $element['#help_link_title'],
        ];
      }

      if (!empty($element['#comments_enable'])) {
        $elements['sub_element']['comments'] = [
          '#type' => 'textarea',
          '#title' => $element['#comments_title'],
          '#title_display' => 'before',
        ];

        if (!empty($element['#comments_required'])) {
          $elements['sub_element']['comments']['#states']['required'] = [
            ':input[name="' . $element['#name'] . '[question]"]' => ['value' => 'yes'],
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * Custom upload file validation.
   */
  public static function uploadFileValidate(
    array &$element,
    FormStateInterface $form_state
  ): void {
    if (
      !empty($element['#machine_name'])
      && !empty($element['#file_required'])
      && $element['#file_required']
    ) {
      $key = $element['#machine_name'];
      $input = $form_state->getUserInput();
      $element_name = $form_state->getTriggeringElement()['#name'];

      if (
        !mb_strpos($element_name, 'fid_upload_button')
        && !empty($input[$key]['question'])
        && $input[$key]['question'] == 'yes'
        && !$input[$key]['file_fid']['fids']
      ) {
        WebformElementHelper::setRequiredError(
          $element,
          $form_state
        );
      }
    }

    if (!empty($element['#files'])) {
      foreach ($element['#files'] as $file) {
        if (!$file->isPermanent()) {
          $file->setPermanent();
          $file->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    self::getValueCallback($element, $input, $form_state);
  }

  /**
   * Performs the after_build callback.
   */
  public static function afterFileBuild(
    array $element,
    FormStateInterface $form_state
  ): array {
    if ($element['#description']) {
      unset($element['#description']);
    }

    return $element;
  }

  /**
   * Validates a composite element.
   */
  public static function validateWebformComposite(
    &$element,
    FormStateInterface $form_state,
    &$complete_form
  ) {
    parent::validateWebformComposite(
      $element,
      $form_state,
      $complete_form
    );

    $value = NestedArray::getValue(
      $form_state->getValues(),
      $element['#parents'] ?? []
    ) ?? [];

    foreach ($value as $name => $item) {
      if ($name !== 'file_fid') {
        switch ($name) {
          case 'help_link':
            $title = $element['#help_link_title'];
            $url = Url::fromUri($element['#help_link_url']);
            $itemValue = Link::fromTextAndUrl($title, $url);
            break;

          case 'details':
            $title = $element['#details_title'];
            break;

          case 'comments':
            $title = $element['#comments_title'];
            $itemValue = $item;
            break;
        }

        $value[$name] = [
          'value' => $itemValue ?? $item,
          'title' => $title ?? $element[$name]['#admin_title'],
        ];
      }
      else {
        $value[$name] = $item;
      }
    }

    $element['#value'] = serialize($value);

    $form_state->setValueForElement($element, $element['#value']);
  }

}
