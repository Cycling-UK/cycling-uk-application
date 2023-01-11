<?php

namespace Drupal\cycling_uk_application_process\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the cycling uk application process entity edit forms.
 */
class CyclingUkApplicationProcessForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $applicationProcessForm = parent::form($form,
      $form_state);
    $applicationProcessForm['dynamics_entity_id']['#disabled'] = TRUE;
    return $applicationProcessForm;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New cycling uk application process %label has been created.', $message_arguments));
        $this->logger('cycling_uk_application_process')->notice('Created new cycling uk application process %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The cycling uk application process %label has been updated.', $message_arguments));
        $this->logger('cycling_uk_application_process')->notice('Updated cycling uk application process %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.cycling_uk_application_process.canonical', ['cycling_uk_application_process' => $entity->id()]);

    return $result;
  }

}
