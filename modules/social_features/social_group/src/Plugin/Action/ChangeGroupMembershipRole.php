<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Change group membership role.
 *
 * @Action(
 *   id = "social_change_group_membership_role",
 *   label = @Translation("Change group membership role"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
class ChangeGroupMembershipRole extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The group storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ViewsBulkOperationSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->storage = $entity_type_manager->getStorage('group');
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupContentInterface && $object->getContentPlugin()->getPluginId() === 'group_membership') {
      $access = $object->access('update', $account, TRUE);
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->formatPlural($this->context['selected_count'], 'Change the role of selected member', 'Change the role of @count selected members');

    $id = $this->routeMatch->getRawParameter('group');

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->storage->load($id)->getGroupType();

    $roles = $group_type->getRoles(FALSE);
    $id = $group_type->getMemberRoleId();
    $roles[$id] = $group_type->getMemberRole();

    /** @var \Drupal\group\Entity\GroupRoleInterface $role */
    foreach ($roles as &$role) {
      $role = $role->label();
    }

    $form['roles'] = [
      '#type' => 'radios',
      '#title' => $this->t('Group roles'),
      '#options' => $roles,
      '#default_value' => $id,
    ];

    unset($form['list']);

    $form['actions']['submit']['#value'] = $this->t('Save');

    return $form;
  }

}