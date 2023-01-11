<?php

namespace Drupal\cycling_uk_application_type\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the cycling uk application type entity edit forms.
 */
class CyclingUkApplicationTypeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New cycling uk application type %label has been created.', $message_arguments));
      $this->logger('cycling_uk_application_type')->notice('Created new cycling uk application type %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The cycling uk application type %label has been updated.', $message_arguments));
      $this->logger('cycling_uk_application_type')->notice('Updated new cycling uk application type %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.cycling_uk_application_type.canonical', ['cycling_uk_application_type' => $entity->id()]);
  }

}
