<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests the Schema.org entity display builder service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilder
 * @group schemadotorg
 */
class SchemaDotOrgEntityDisplayBuilderTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * The Schema.org entity display builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface
   */
  protected $schemaEntityDisplayBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaEntityDisplayBuilder = $this->container->get('schemadotorg.entity_display_builder');
  }

  /**
   * Test Schema.org entity display builder.
   */
  public function testEntityDisplayBuilder(): void {
    $this->createSchemaEntity('node', 'thing');

    // Check getting default field weights.
    $default_field_weights = $this->schemaEntityDisplayBuilder->getDefaultFieldWeights();
    $this->assertEquals(1, $default_field_weights['name']);
    $this->assertEquals(2, $default_field_weights['title']);
    $this->assertEquals(3, $default_field_weights['alternateName']);
    $this->assertEquals(4, $default_field_weights['description']);

    // Check setting entity displays for a field.
    $this->schemaEntityDisplayBuilder->setFieldDisplays(
      [
        'entity_type' => 'node',
        'bundle' => 'thing',
        'field_name' => 'name',
      ],
      NULL,
      [],
      NULL,
      []
    );
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_view_display = EntityViewDisplay::load('node.thing.default');
    $expected_value = [
      'settings' => [],
      'third_party_settings' => [],
      'weight' => 101,
      'region' => 'content',
    ];
    $this->assertEquals($expected_value, $entity_view_display->getComponent('name'));

    // Check setting entity display field weights for Schema.org properties.
    $this->schemaEntityDisplayBuilder->setFieldWeights('node', 'thing', ['name' => 'name']);

    \Drupal::entityTypeManager()->getStorage('entity_view_display')->resetCache();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_view_display = EntityViewDisplay::load('node.thing.default');
    $expected_value = [
      'settings' => [],
      'third_party_settings' => [],
      'weight' => 1,
      'region' => 'content',
    ];
    $this->assertEquals($expected_value, $entity_view_display->getComponent('name'));

    // Check determining if a display is node teaser view display.
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'teaser',
    ]);
    $this->assertTrue($this->schemaEntityDisplayBuilder->isNodeTeaserDisplay($entity_view_display));

    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'not_teaser',
    ]);
    $this->assertFalse($this->schemaEntityDisplayBuilder->isNodeTeaserDisplay($entity_view_display));

    // Check getting display form modes for a specific entity type.
    $this->assertEquals(['default' => 'default'], $this->schemaEntityDisplayBuilder->getFormModes('node', 'page'));

    // Check getting display view modes for a specific entity type.
    $this->assertEquals(['default' => 'default'], $this->schemaEntityDisplayBuilder->getViewModes('node', 'page'));
  }

}
