<?php

namespace Drupal\Tests\mapping_set\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org mapping set manager.
 *
 * @covers \Drupal\mapping_set\SchemaDotOrgTaxonomyPropertyVocabularyManagerTest;
 * @group schemadotorg
 */
class SchemaDotOrgMappingSetManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'schemadotorg_mapping_set',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org mapping set manager service.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['schemadotorg_mapping_set']);
    $this->installEntityDependencies('media');
    $this->installEntityDependencies('node');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->schemaMappingSetManager = $this->container->get('schemadotorg_mapping_set.manager');
  }

  /**
   * Test Schema.org mapping set manager.
   */
  public function testManager() {
    // Update mapping sets to simples sets.
    $config = \Drupal::configFactory()->getEditable('schemadotorg_mapping_set.settings');
    $config->set('sets', [
      'required' => [
        'label' => 'Required',
        'types' => ['node:ContactPoint'],
      ],
      'common' => [
        'label' => 'Common',
        'types' => ['node:Place'],
      ],
    ])->save();

    // Check determining if a Schema.org mapping set is already setup.
    $this->assertFalse($this->schemaMappingSetManager->isSetup('required'));
    $this->assertFalse($this->schemaMappingSetManager->isSetup('common'));

    // Check determining if a mapping set type is valid.
    $this->assertFalse($this->schemaMappingSetManager->isValidType('test'));
    $this->assertFalse($this->schemaMappingSetManager->isValidType('node:Test'));
    $this->assertFalse($this->schemaMappingSetManager->isValidType('test:Thing'));
    $this->assertTrue($this->schemaMappingSetManager->isValidType('node:Thing'));

    // Check getting Schema.org types from mapping set name.
    $this->assertEquals(['node:Place' => 'node:Place'], $this->schemaMappingSetManager->getTypes('common'));
    $this->assertEquals([
      'node:ContactPoint' => 'node:ContactPoint',
      'node:Place' => 'node:Place',
    ], $this->schemaMappingSetManager->getTypes('common', TRUE));

    // Check setting up the Schema.org mapping set.
    $this->assertEmpty($this->entityTypeManager->getStorage('node_type')->getQuery()->accessCheck()->execute());
    $this->schemaMappingSetManager->setup('common');
    $this->assertNotEmpty($this->entityTypeManager->getStorage('node_type')->getQuery()->accessCheck()->execute());
    $this->assertEquals(['contact_point' => 'contact_point', 'place' => 'place'], $this->entityTypeManager->getStorage('node_type')->getQuery()->accessCheck()->execute());
    $this->assertEquals(['node.contact_point' => 'node.contact_point', 'node.place' => 'node.place'], $this->entityTypeManager->getStorage('schemadotorg_mapping')->getQuery()->accessCheck()->execute());

    // Check determining if a Schema.org mapping set is already setup.
    $this->assertTrue($this->schemaMappingSetManager->isSetup('required'));
    $this->assertTrue($this->schemaMappingSetManager->isSetup('common'));

    // Check that devel_generate.module is required to generate content.
    try {
      $this->schemaMappingSetManager->generate('common');
    }
    catch (\Exception $exception) {
      $this->assertEquals('The devel_generate.module needs to be enabled.', $exception->getMessage());
    }

    // Check that devel_generate.module is required to kill content.
    try {
      $this->schemaMappingSetManager->kill('common');
    }
    catch (\Exception $exception) {
      $this->assertEquals('The devel_generate.module needs to be enabled.', $exception->getMessage());
    }

    // Check tearing down the Schema.org mapping set.
    $this->schemaMappingSetManager->teardown('common');
    $this->assertEquals(['contact_point' => 'contact_point'], $this->entityTypeManager->getStorage('node_type')->getQuery()->accessCheck()->execute());
    $this->assertEquals(['node.contact_point' => 'node.contact_point'], $this->entityTypeManager->getStorage('schemadotorg_mapping')->getQuery()->accessCheck()->execute());

    $this->schemaMappingSetManager->teardown('required');
    $this->assertEquals([], $this->entityTypeManager->getStorage('node_type')->getQuery()->accessCheck()->execute());
    $this->assertEquals([], $this->entityTypeManager->getStorage('schemadotorg_mapping')->getQuery()->accessCheck()->execute());
  }

}
