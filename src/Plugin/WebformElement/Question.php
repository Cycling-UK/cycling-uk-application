<?php

namespace Drupal\cycling_uk_application\Plugin\WebformElement;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\cycling_uk_application\WebFormTrait;
use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'dynamics_question' element.
 *
 * @WebformElement(
 *   id = "dynamics_question",
 *   label = @Translation("Dynamics: Question"),
 *   description = @Translation("Provides a webform composite element."),
 *   category = @Translation("Composite elements"),
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 */
class Question extends WebformElementBase {

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
      // Question radio section.
      'question_title' => '',
      'question_type' => 'yes_no',
      'question_required' => FALSE,
      // Photo evidence section.
      'file_enable' => FALSE,
      'file_title' => '',
      'file_max_upload' => 10,
      'file_allowed_ext' => 'gif jpg jpeg png pdf doc docx ppt pptx xls xlsx',
      'file_required' => FALSE,
      // Further details section.
      'details_enable' => FALSE,
      'details_type' => '',
      'details_options' => [],
      'details_option_other' => FALSE,
      'details_title' => '',
      'details_required' => FALSE,
      // Help text section.
      'help_text' => '',
      'help_format' => 'full_html',
      // Help link section.
      'help_link_title' => '',
      'help_link_url' => '',
      // Comments section.
      'comments_enable' => FALSE,
      'comments_title' => '',
      'comments_required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $values = $form_state->get('element_properties');

    $form['element']['question'] = [
      '#type' => 'details',
      '#title' => $this->t('Question'),
      '#open' => TRUE,
    ];

    $form['element']['question']['question_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
    ];

    $form['element']['question']['question_type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'radios',
      '#options' => [
        'yes_no' => $this->t('Yes/No'),
        'yes_no_unsure' => $this->t('Yes/No/Unsure'),
      ],
    ];

    $form['element']['question']['question_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
    ];

    $form['element']['file'] = [
      '#type' => 'details',
      '#title' => $this->t('File'),
      '#open' => TRUE,
    ];

    $form['element']['file']['file_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
    ];

    $form['element']['file']['file_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $values['file_title'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="properties[file_enable]"]' => ['checked' => TRUE]],
        ],
        'required' => [
          [':input[name="properties[file_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['element']['file']['file_allowed_ext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Allowed file extensions for upload.'),
      '#states' => [
        'visible' => [
          [':input[name="properties[file_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    // phpcs:ignore
    $max_filesize = \Drupal::config('webform.settings')
      ->get('file.default_max_filesize')
      ?: Environment::getUploadMaxSize();

    $max_filesize = Bytes::toNumber($max_filesize);
    $max_filesize = ($max_filesize / 1024 / 1024);

    $form['element']['file']['file_max_upload'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum file size'),
      '#field_suffix' => $this->t(
        'MB (Max: @filesize MB)',
        ['@filesize' => $max_filesize]
      ),
      '#description' => $this->t(
        'Enter the max file size a user may upload.'
      ),
      '#min' => 1,
      '#states' => [
        'visible' => [
          [':input[name="properties[file_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['element']['file']['file_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#states' => [
        'visible' => [
          [':input[name="properties[file_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['element']['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Details'),
      '#open' => TRUE,
    ];

    $form['element']['details']['details_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
    ];

    $form['element']['details']['details_type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'radios',
      '#options' => [
        'textarea' => $this->t('Text area'),
        'select' => $this->t('Dropdown'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="properties[details_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['element']['details']['details_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#states' => [
        'visible' => [
          [':input[name="properties[details_type]"]' => ['value' => 'select']],
          [':input[name="properties[details_type]"]' => ['value' => 'textarea']],
        ],
        'required' => [
          [':input[name="properties[details_type]"]' => ['value' => 'select']],
          [':input[name="properties[details_type]"]' => ['value' => 'textarea']],
        ],
      ],
    ];

    $form['element']['details']['details_options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Options'),
      '#description' => $this->t(
        "Enter one key: 'value' per line for build dropdown options. An example: <pre>tailor_made: 'We have a tailor made cycle storage facility'</pre>"
      ),
      '#states' => [
        'visible' => [
          [':input[name="properties[details_type]"]' => ['value' => 'select']],
        ],
      ],
    ];

    $form['element']['details']['details_option_other'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include "Other" option'),
      '#states' => [
        'visible' => [
          [':input[name="properties[details_type]"]' => ['value' => 'select']],
        ],
      ],
    ];

    $form['element']['details']['details_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#states' => [
        'visible' => [
          [':input[name="properties[details_type]"]' => ['value' => 'select']],
          [':input[name="properties[details_type]"]' => ['value' => 'textarea']],
        ],
      ],
    ];

    $form['element']['help_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Help text'),
      '#format' => $values['help_format'] ?? 'full_html',
    ];

    $form['element']['help_link'] = [
      '#type' => 'details',
      '#title' => 'Help Link',
      '#open' => TRUE,
    ];

    $form['element']['help_link']['help_link_title'] = [
      '#type' => 'textfield',
      '#title' => 'Link Title',
    ];

    $form['element']['help_link']['help_link_url'] = [
      '#type' => 'textfield',
      '#title' => 'URL',
    ];

    $form['element']['comments_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Comments'),
      '#format' => $values['comments_format'] ?? 'full_html',
    ];

    $form['element']['comments'] = [
      '#type' => 'details',
      '#title' => $this->t('Comments'),
      '#open' => TRUE,
    ];

    $form['element']['comments']['comments_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
    ];

    $form['element']['comments']['comments_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#states' => [
        'visible' => [
          [':input[name="properties[comments_enable]"]' => ['checked' => TRUE]],
        ],
        'required' => [
          [':input[name="properties[comments_enable]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['element']['comments']['comments_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#states' => [
        'visible' => [
          [':input[name="properties[comments_enable]"]' => ['checked' => TRUE]],
        ],
      ],
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
    $options['multiline'] = $this->isMultiline($element);
    $format_function = 'format' . ucfirst($format);

    $element_value = $this->$format_function(
      $element,
      $webform_submission,
      $options
    );

    // Handle empty value or empty array.
    if (
      $element_value === ''
      || (
        is_array($element_value)
        && $element_value === []
      )
    ) {
      // Return NULL if empty is excluded.
      if ($this->isEmptyExcluded($element, $options)) {
        return NULL;
      }
      // Else set the formatted value to empty message/placeholder.
      else {
        $element_value = $this
          ->configFactory
          ->get('webform.settings')
          ->get('element.empty_message');
      }
    }

    $items = [];
    if (!empty($element_value)) {
      if (is_array($element_value)) {
        // phpcs:ignore
        $values = @unserialize(reset($element_value));
      }
      elseif ($element_value === '{Empty}') {
        $values[$element['#webform_key']]['value'] = $element_value;
        $values[$element['#webform_key']]['title'] = $element['#title'];
      }
      else {
        // phpcs:ignore
        $values = @unserialize($element_value);
      }

      $comparedValue = '';
      foreach ($values as $key => $valueItem) {
        if ($valueItem) {
          if ($key === 'file_fid') {
            $file = File::load($valueItem);
            $valueItem = [];
            $valueItem['value'] = '';
            $valueItem['title'] = $element['#file_title'];
            if ($file) {
              $uri = $file->getFileUri();
              $url = Url::fromUri(\Drupal::service('file_url_generator')
                ->generateAbsoluteString($uri));
              $valueItem['value'] = Link::fromTextAndUrl($element['#file_title'], $url);
            }
          }
          if (
            $key === 'details'
            && $element['#details_type'] === 'select'
          ) {
            $valueItem['value'] = $element['#details_options'][$valueItem['value']] ?? '';
          }

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
                if ($value instanceof Link) {
                  $item['#title'] = '';
                  $item['link'] = $value->toRenderable();
                }
                else {
                  $item['#markup'] = $value;
                }
              }

              $items[] = $item;
            }
            else {
              if ($value instanceof Link) {
                $value = $value->toString();
              }
              $comparedValue .= $title . ': ' . $value . '; ';
            }
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

}
