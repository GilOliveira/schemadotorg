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
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org JSON-LD builder.
   */
  public function testBuilder() {
    $options = ['default-properties' => ['name', 'alternateName', 'description']];
    $this->createSchemaEntity('node', 'Thing', $options);

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
      'body' => [
        'value' => 'Some description',
        'format' => 'empty_format',
      ],
    ]);
    $node->save();

    // Check building JSON-LD for an entity that is mapped to a Schema.org type.
    $expected_result = [
      '@context' => 'https://schema.org',
      '@type' => 'Thing',
      'identifier' => [
          [
            '@type' => 'PropertyValue',
            'propertyID' => 'uuid',
            'value' => $node->uuid(),
          ],
      ],
      'name' => 'Something',
      'alternateName' => 'Something else',
      'description' => 'Some description',
    ];
    $this->assertEquals($expected_result, $this->builder->build($node));
  }

}
