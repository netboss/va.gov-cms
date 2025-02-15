<?php

namespace Drupal\va_gov_backend\EventSubscriber;

use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\va_gov_user\Service\UserPermsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * VA.gov VAMC Entity Event Subscriber.
 */
class EntityEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The User Perms Service.
   *
   * @var \Drupal\va_gov_user\Service\UserPermsService
   */
  protected $userPermsService;

  /**
   * Constructs the EventSubscriber object.
   *
   * @param \Drupal\va_gov_user\Service\UserPermsService $user_perms_service
   *   The current user perms service.
   */
  public function __construct(
    UserPermsService $user_perms_service
  ) {
    $this->userPermsService = $user_perms_service;
  }

  /**
   * Entity presave Event call.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   The event.
   */
  public function entityPresave(EntityPresaveEvent $event): void {
    $entity = $event->getEntity();
    if ($entity instanceof NodeInterface) {
      $this->trimNodeTitleWhitespace($entity);
    }
  }

  /**
   * Trim any preceding and trailing whitespace on node titles.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be modified.
   */
  private function trimNodeTitleWhitespace(NodeInterface $node) {
    $title = $node->getTitle();
    // Trim leading and then trailing separately to avoid a convoluted regex.
    $title = preg_replace('/^\s+/', '', $title);
    $title = preg_replace('/\s+$/', '', $title);
    $node->setTitle($title);
  }

  /**
   * Form alterations for staff profile content type.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event.
   */
  public function alterstaffProfileNodeForm(FormIdAlterEvent $event): void {
    $this->addStateManagementToBioFields($event);
  }

  /**
   * Add states management to bio fields to determine visibility based on bool.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event.
   */
  public function addStateManagementToBioFields(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form['#submit'][] = $this->addStateManagementToBioFieldsSubmitHandler($event);
    $form['#attached']['library'][] = 'va_gov_backend/set_body_to_required';
    $selector = ':input[name="field_complete_biography_create[value]"]';
    $form['field_intro_text']['widget'][0]['value']['#states'] = [
      'required' => [
          [$selector => ['checked' => TRUE]],
      ],
      'visible' => [
            [$selector => ['checked' => TRUE]],
      ],
    ];
    $form['field_body']['#states'] = [
      'visible' => [
          [$selector => ['checked' => TRUE]],
      ],
    ];
    $form['field_complete_biography']['#states'] = [
      'visible' => [
          [$selector => ['checked' => TRUE]],
      ],
    ];
  }

  /**
   * Submit handler removes field body req when bio toggle is set to FALSE.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event.
   */
  public function addStateManagementToBioFieldsSubmitHandler(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form_state = $event->getFormState();
    $bio_display = !empty($form_state->getUserInput()['field_complete_biography_create']['value']) ? TRUE : FALSE;
    if (!$bio_display) {
      $form['field_body']['widget'][0]['#required'] = FALSE;
    }
  }

  /**
   * Form alterations for centralized content edit form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event.
   */
  public function alterCentralizedContentNodeForm(FormIdAlterEvent $event): void {
    $form = &$event->getForm();
    $this->lockCentralizedContentFields($form);
    $this->lockCcDocumentorParagraphs($form);
  }

  /**
   * Locks down form paragraphs for non-admins.
   *
   * @param array $form
   *   The form.
   */
  public function lockCcDocumentorParagraphs(array &$form) {
    if (!$this->userPermsService->hasAdminRole(TRUE)) {
      // Loop through our descriptor & wysiwyg paragraphs, and add special
      // treatment class and header tags, and disable wysiwygs.
      foreach ($form['field_content_block']['widget'] as $key => $cc_paragraph) {
        if (is_numeric($key) && $cc_paragraph['#paragraph_type'] === 'centralized_content_descriptor') {
          $form['field_content_block']['widget'][$key]['_weight']['#attributes']['disabled'] = TRUE;
          $form['field_content_block']['widget'][$key]['#attributes']['class'] = [
            'cc-special-treatment-paragraph',
            $form['field_content_block']['widget'][$key]['#paragraph_type'],
          ];
          $title = $form['field_content_block']['widget'][$key]['subform']['field_cc_documentor_title']['widget'][0]['value']['#default_value'];
          $title = $this->t(':title', [':title' => $title]);
          $description = $form['field_content_block']['widget'][$key]['subform']['field_cc_documentor_description']['widget'][0]['#default_value'];
          $description = $this->t(':description', [':description' => $description]);
          $form['field_content_block']['widget'][$key]['#prefix'] = "<h3>{$title}</h3><p>{$description}</p>";
        }
      }
    }
  }

  /**
   * Locks down form fields for non-admins.
   *
   * @param array $form
   *   The form.
   */
  public function lockCentralizedContentFields(array &$form) {
    // All users should see this intro.
    $about = $this->t('About');
    $description = $this->t("Centralized content ensures that Veterans receive the same information about certain topics no matter where they're presented on VA.gov. Here you'll mangage content components that contain pieces of standardized information. Review the about info below to understand where this content will be applied.");
    $form['title']['#prefix'] = "<h2>{$about}</h2><p>{$description}</p>";
    $form['title']['#attributes'] = [
      'class' => ['cc-special-treatment-field'],
    ];
    $question = $this->t('Need to add a new centralized content component not listed here?');
    $contact = $this->t('Contact the product owner associated with this content.');
    $form['field_content_block']['#suffix'] = "<div class=\"cc-suffix-text\"><strong>{$question}</strong><br />{$contact}</div>";

    if (!$this->userPermsService->hasAdminRole(TRUE)) {
      $form['#attached']['library'][] = 'va_gov_backend/centralized_content_alterations';

      // For non-admin replace field with static content.
      unset($form['field_content_block']['widget']['#title']);
      $form['field_content_block']['widget']['#title'] = '<h2>' . $this->t('Content') . '</h2>';

      // For non-admin replace body field with static content
      // generated from field value.
      if (!empty($form['body']['widget'][0]['#default_value'])) {
        $form['body'] = [
          '#markup' => "<h3>{$this->t('Purpose')}</h3><div class=\"cc-wysi-wrap\">{$form['body']['widget'][0]['#default_value']}</div>",
        ];
      }
      else {
        unset($form['body']);
      }

      // For non-admin replace product field with static content
      // generated from field value and disable editing.
      if (!empty($form['field_product']['widget']['#default_value'][0])) {
        $title = $this->t(':title', [':title' => $form['field_product']['widget']['#title']]);
        $description = $this->t(':description', [':description' => $form['field_product']['widget']['#options'][$form['field_product']['widget']['#default_value'][0]]]);
        $form['field_product'] = [
          '#prefix' => "<h3>{$title}</h3><p class=\"cc-p\">{$description}</p>",
          '#attributes' => [
            'class' => ['cc-special-treatment-field'],
          ],
        ];
      }
      else {
        unset($form['field_product']);
      }

      // For non-admin replace applied to field with static content
      // generated from field value and disable editing.
      if (!empty($form['field_applied_to']['widget'][0]['value']['#default_value'])) {
        $title = $this->t(':title', [':title' => $form['field_applied_to']['widget'][0]['#title']]);
        $description = $this->t(':description', [':description' => $form['field_applied_to']['widget'][0]['value']['#default_value']]);
        $form['field_applied_to'] = [
          '#prefix' => "<h3>{$title}</h3><p class=\"cc-p\">{$description}</p>",
          '#attributes' => [
            'class' => ['cc-special-treatment-field'],
          ],
        ];
      }
      else {
        unset($form['field_applied_to']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'entityPresave',
      'hook_event_dispatcher.form_node_person_profile_form.alter' => 'alterStaffProfileNodeForm',
      'hook_event_dispatcher.form_node_person_profile_edit_form.alter' => 'alterStaffProfileNodeForm',
      'hook_event_dispatcher.form_node_centralized_content_edit_form.alter' => 'alterCentralizedContentNodeForm',
    ];
  }

}
