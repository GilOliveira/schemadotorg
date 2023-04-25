<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_starterkit\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgConfigSnapshotTestBase;

/**
 * Tests the generated configuration files against a config snapshot.
 *
 * @group schemadotorg
 */
class SchemaDotOrgStarterKitConfigSnapshotTest extends SchemaDotOrgConfigSnapshotTestBase {

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  /**
   * Disabled config schema checking temporarily until smart date fixes missing schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_starterkit_test'];

  /**
   * {@inheritdoc}
   */
  protected $snapshotDirectory = __DIR__ . '/../../schemadotorg/config/snapshot';

}
