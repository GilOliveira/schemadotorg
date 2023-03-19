<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_taxonomy\Kernel;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org taxonomy installation.
 *
 * @covers \schemadotorg_taxonomy_install()
 * @group schemadotorg
 */
class SchemaDotOrgTaxonomyInstallTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'taxonomy',
    'schemadotorg_taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['schemadotorg_taxonomy']);
  }

  /**
   * Test Schema.org taxonomy installation.
   */
  public function testInstall(): void {
    \Drupal::moduleHandler()->loadInclude('schemadotorg_taxonomy', 'install');
    schemadotorg_taxonomy_install(FALSE);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = SchemaDotOrgMapping::load('taxonomy_term.tags');

    // Confirm taxonomy term mapping is created and mapped to DefinedTerm.
    $this->assertEquals('DefinedTerm', $mapping->getSchemaType());
  }

}
