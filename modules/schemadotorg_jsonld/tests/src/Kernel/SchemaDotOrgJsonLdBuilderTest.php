<?php

namespace Drupal\Tests\schemadotorg_jsonld\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD builder.
 *
 * @covers \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilder;
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdBuilderTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'filter',
    'schemadotorg_jsonld',
  ];

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org JSON-LD builder.
   */
  public function testBuilder() {
    // Allow Schema.org Thing to have default properties.
    $this->config('schemadotorg.settings')
      ->set('schema_types.default_properties.Thing', ['name', 'alternateName', 'description', 'subjectOf'])
      ->save();

    $this->createSchemaEntity('node', 'Thing');

    FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ])->save();

    $node = Node::create([
      'type' => 'thing',
      'title' => 'Something',
      'schema_alternate_name' => [
        'value' => 'Something else',
      ],
      'schema_subject_of' => [
        'value' => 'Some subject',
      ],
      'body' => [
        'value' => 'Some description',
        'format' => 'empty_format',
      ],
    ]);
    $node->save();

    // Check building JSON-LD for an entity that is mapped to a Schema.org type.
    $expected_result = [
      '@type' => 'Thing',
      'identifier' => [
          [
            '@type' => 'PropertyValue',
            'propertyID' => 'uuid',
            'value' => $node->uuid(),
          ],
      ],
      'name' => 'Something',
      'alternateName' => [
        'Something else',
      ],
      'description' => 'Some description',
      'subjectOf' => [
        '@type' => 'CreativeWork',
        'name' => 'Some subject',
      ],
    ];
    $this->assertEquals($expected_result, $this->builder->buildEntity($node));

    // Check building JSON-LD for an entity without an identifier property.
    $json_ld = $this->builder->buildEntity($node, ['identifier' => FALSE]);
    $this->assertArrayHasKey('@type', $json_ld);
    $this->assertArrayNotHasKey('identifier', $json_ld);
  }

}
