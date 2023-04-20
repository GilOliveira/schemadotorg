<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

use Drupal\devel_generate\DevelGeneratePluginManager;

/**
 * Schema.org starterkit manager service.
 */
class SchemaDotOrgStarterkitGenerate implements SchemaDotOrgStarterkitGeneralInterface {

  /**
   * Constructs a SchemaDotOrgStarterkitGenerate object.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager|null $develGenerateManager
   *   The Devel generate manager.
   */
  public function __construct(
    protected ?DevelGeneratePluginManager $develGenerateManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generate(array $entity_types): void {
    if (is_null($this->develGenerateManager)) {
      return;
    }

    // Mapping entity type to devel-generate command with default options.
    $commands = [
      'user' => ['users', ['roles' => NULL]],
      'node' => ['content', ['add-type-label' => TRUE]],
      'media' => ['media'],
      'taxonomy_term' => ['term'],
    ];
    foreach ($entity_types as $entity_type => $bundles) {
      if (!isset($commands[$entity_type])) {
        continue;
      }

      $devel_generate_plugin_id = $commands[$entity_type][0];
      foreach ($bundles as $bundle => $num) {
        // Args.
        $args = [(string) $num];
        // Options.
        $options = $commands[$entity_type][1] ?? [];
        $options += [
          'kill' => TRUE,
          'bundles' => $bundle,
          'media-types' => $bundles,
          // Setting the below options to NULL prevents PHP warnings.
          'base-fields' => NULL,
          'skip-fields' => NULL,
          'authors' => NULL,
          'feedback' => NULL,
          'languages' => NULL,
          'translations' => NULL,
        ];

        // Plugin.
        /** @var \Drupal\devel_generate\DevelGenerateBaseInterface $devel_generate_plugin */
        $devel_generate_plugin = $this->develGenerateManager->createInstance($devel_generate_plugin_id);
        // Parameters.
        $parameters = $devel_generate_plugin->validateDrushParams($args, $options);
        // Generate.
        $devel_generate_plugin->generate($parameters);
      }
    }
  }

}
