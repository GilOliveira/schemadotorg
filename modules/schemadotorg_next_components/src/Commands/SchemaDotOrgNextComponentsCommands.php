<?php

namespace Drupal\schemadotorg_next_components\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg_next_components\SchemaDotOrgNextComponentsBuilderInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Next.js components Drush commands.
 */
class SchemaDotOrgNextComponentsCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org Next.js components builder.
   *
   * @var \Drupal\schemadotorg_next_components\SchemaDotOrgNextComponentsBuilderInterface
   */
  protected $componentsBuilder;

  /**
   * SchemaDotOrgNextComponentsCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_next_components\SchemaDotOrgNextComponentsBuilderInterface $components_builder
   *   The Schema.org Next.js components builder.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgNextComponentsBuilderInterface $components_builder
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->componentsBuilder = $components_builder;
  }

  /**
   * Validates the Next.js components to be created.
   *
   * @hook validate schemadotorg_next_components:create
   */
  public function createValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $destination = $arguments['destination'];

    if (empty($destination)) {
      throw new \Exception(dt('Destination is required.'));
    }

    if (!file_exists($destination)) {
      throw new \Exception(dt('Destination is not found.'));
    }

    if (!is_dir($destination)) {
      throw new \Exception(dt('Destination is not a directory.'));
    }
  }

  /**
   * Create Schema.org Next.js components.
   *
   * @param string $destination
   *   The destination.
   *
   * @command schemadotorg_next_components:create
   *
   * @usage schemadotorg_next_components:create next-app/components
   *
   * @aliases sonc
   */
  public function create(string $destination) {
    if (!$this->io()->confirm(dt('Are you sure you want to generate Next.js components?'))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\node\NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    $entity_type_id = 'node';
    foreach ($node_types as $node_type) {
      $bundle = $node_type->id();

      $file_name = "$entity_type_id--$bundle.tsx";
      $output = $this->componentsBuilder->build($entity_type_id, $bundle);
      file_put_contents("$destination/$file_name", $output);

      $this->io()->writeln(dt('Created @name', ['@name' => $file_name]));
    }
  }

}