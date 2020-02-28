<?php

namespace Drupal\sparklearn_live_preview\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\node\NodeForm;
use Drupal\Core\Form\FormStateInterface;;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Overrides edit form for node preview.
 */
class SparklearnLivePreviewNodeForm extends NodeForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $node = $this->entity;
    $preview_mode = $node->type->entity->getPreviewMode();

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || $form_state->get('has_been_previewed');

    unset($element['preview']['#submit']);
    $element['preview']['#ajax'] = [
      'callback' => '::livePreview',
    ];

    return $element;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function livePreview(array $form, FormStateInterface $form_state) {
    $this->submitForm($form, $form_state);

    $node_preview = $form_state->getFormObject()->getEntity();
    $node_preview->in_preview = TRUE;

    // Get config from Block.
    $config = \Drupal::config('block.block.livepreviewblock');

    // Use full mode for Learn Articles.
    if ($node_preview->getType() == 'learn_article') {
      $node_preview->preview_view_mode = 'full';
    }
    else {
      $node_preview->preview_view_mode = $config->get('settings.view_mode');
    }

    $inline_entity_form_widgets = $form_state->get('inline_entity_form');

    foreach ($inline_entity_form_widgets as &$widget_state) {
      foreach ($node_preview as $field) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
        $definition = $field->getFieldDefinition();

        // Only act on entity_reference fields.
        if ($definition->getType() != 'entity_reference') {
          continue;
        }

        // Skip empty fields.
        if (empty($widget_state['instance'])) {
          continue;
        }

        if ($field->getName() === $widget_state['instance']->getName()) {
          $node_preview->{$field->getName()}->setValue($widget_state['entities']);
        }
      }
    }

    $build = $this->entityTypeManager
      ->getViewBuilder($node_preview->getEntityTypeId())
      ->view($node_preview, $node_preview->preview_view_mode);

    $build['#attached']['library'][] = 'node/drupal.node.preview';
    $build['#attached']['library'][] = 'sparklearn_live_preview/sparklearn_live_preview-lib';
    $build['#attributes']['class'][] = 'c-block-sparklearn-live-preview';
    $build['#cache'] = ['max-age' => 0];

    // The ajax response object we are going to return.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(".c-block-sparklearn-live-preview", $build));
    // Set link targets in live preview body to new tab/window.
    $response->addCommand(new InvokeCommand(NULL, 'set_target_new', []));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Clear validation errors.
    if ($form_state->getValue('op') == 'Preview') {
      $form_state->clearErrors();
    }
    else {
      return parent::validateForm($form, $form_state);
    }

  }

}
