<?php

namespace Drupal\Tests\schemadotorg_jsonld\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD date time range builder.
 *
 * @covers \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManager::getSchemaPropertyValue;
 * @covers datetime_range_schemadotorg_jsonld_schema_type_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdDateTimeRangeBuilderTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'filter',
    'datetime_range',
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
    // Reset Event properties to use eventSchedule and startDate with daterange.
    $config = $this->config('schemadotorg.settings');
    $config
      ->set('schema_types.default_properties.Event', ['eventSchedule', 'inLanguage', 'name', 'startDate'])
      ->set('schema_properties.default_fields.startDate', ['type' => 'daterange'])
      ->save();

    $this->createSchemaEntity('node', 'Event');

    // Event node.
    $event_node = Node::create([
      'type' => 'event',
      'title' => 'Sometime',
      'schema_start_date' => [
        [
          'value' => '2001-01-01T11:00:00',
          'end_value' => '2001-01-01T12:00:00',
        ],
      ],
      'schema_event_schedule' => [
        [
          'value' => '2001-01-01T11:00:00',
          'end_value' => '2001-01-01T12:00:00',
        ],
      ],
    ]);
    $event_node->save();

    // Event schedule.
    $expected_value = [
      '@type' => 'Event',
      'identifier' =>
        [
          [
            '@type' => 'PropertyValue',
            'propertyID' => 'uuid',
            'value' => $event_node->uuid(),
          ],
        ],
      'inLanguage' => 'en',
      'name' => 'Sometime',
      'eventSchedule' => [
        [
          '@type' => 'Schedule',
          'startDate' => '2001-01-01T11:00:00',
          'endDate' => '2001-01-01T12:00:00',
        ],
      ],
      'startDate' => '2001-01-01T11:00:00',
      'endDate' => '2001-01-01T12:00:00',
    ];
    $actual_value = $this->builder->buildEntity($event_node);
    $this->assertEquals($expected_value, $actual_value);
  }

}
