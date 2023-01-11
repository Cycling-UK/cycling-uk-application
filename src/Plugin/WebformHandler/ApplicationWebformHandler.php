<?php

namespace Drupal\cycling_uk_application\Plugin\WebformHandler;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;

/**
 * Dynamics webform handler.
 *
 * @WebformHandler(
 *   id = "application_webform_handler",
 *   label = @Translation("Application webform handler"),
 *   category = @Translation("External"),
 *   description = @Translation("Queues webform submissions to be sent to Dynamics."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 *   tokens = TRUE,
 * )
 */
final class ApplicationWebformHandler extends WebformHandlerBase {

}
