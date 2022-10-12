<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld\Kernel;

use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * Tests the functionality of the Schema.org Smart Date installer.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSmartDateInstallerTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg',
    'schemadotorg_smart_date',
  ];

  /**
   * Schema.org Smart Date installer.
   *
   * @var \Drupal\schemadotorg_smart_date\SchemadotorgSmartDateInstallerInterface
   */
  protected $installer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg']);

    $this->installer = \Drupal::service('schemadotorg_smartdate.installer');
  }

  /**
   * Test Schema.org Smart Date installer.
   */
  public function testInstaller(): void {
    $config = $this->config('schemadotorg.settings');

    // Check performing setup tasks when the Schema.org Smart Date module is installed.
    $this->installer->install(FALSE);

    $event_properties = $config->get('schema_types.default_properties.Event');
    $this->assertTrue(in_array('eventSchedule', $event_properties));
    $this->assertFalse(in_array('startDate', $event_properties));
    $this->assertFalse(in_array('endDate', $event_properties));

    $event_schedule = $config->get('schema_properties.default_fields.eventSchedule');
    $this->assertEquals(['type' => 'smartdate', 'unlimited' => TRUE], $event_schedule);

    // Check removing any information that the Schema.org Smart Date module sets.
    $this->installer->uninstall(FALSE);

    $event_properties = $config->get('schema_types.default_properties.Event');
    $this->assertFalse(in_array('eventSchedule', $event_properties));
    $this->assertTrue(in_array('startDate', $event_properties));
    $this->assertTrue(in_array('endDate', $event_properties));

    $event_schedule = $config->get('schema_properties.default_fields.eventSchedule');
    $this->assertEquals(['type' => 'daterange', 'unlimited' => TRUE], $event_schedule);
  }

}
