<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_smart_date;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Schema.org Smart Date installer service.
 */
class SchemadotorgSmartDateInstaller implements SchemadotorgSmartDateInstallerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SchemadotorgSmartDateInstaller object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function install(bool $is_syncing): void {
    if ($is_syncing) {
      return;
    }

    $this->setDefaultProperties(
      'Event',
      ['eventSchedule'],
      ['startDate', 'endDate', 'duration']
    );

    // Set eventSchedule to use a multiple smartdate field.
    $this->configFactory
      ->getEditable('schemadotorg.settings')
      ->set('schema_properties.default_fields.eventSchedule', [
        'type' => 'smartdate',
        'unlimited' => TRUE,
      ])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(bool $is_syncing): void {
    if ($is_syncing) {
      return;
    }

    // Restore startDate and endDate from Event default properties
    // and remove eventSchedule.
    $this->setDefaultProperties(
      'Event',
      ['startDate', 'endDate', 'duration'],
      ['eventSchedule'],
    );

    // Restore eventSchedule to use multiple daterange fields.
    $this->configFactory
      ->getEditable('schemadotorg.settings')
      ->set('schema_properties.default_fields.eventSchedule', [
        'type' => 'daterange',
        'unlimited' => TRUE,
      ])
      ->save();
  }

  /**
   * Update a Schema.org type's default properties.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $add
   *   Schema.org properties to be removed.
   * @param array $remove
   *   Schema.org properties to be added.
   */
  protected function setDefaultProperties(string $schema_type, array $add, array $remove): void {
    $config = $this->configFactory->getEditable('schemadotorg.settings');
    $default_properties = $config->get("schema_types.default_properties.$schema_type");
    if (!$default_properties) {
      return;
    }

    $default_properties = array_filter($default_properties, function ($property) use ($remove) {
      return !in_array($property, $remove);
    });
    $default_properties = array_merge($default_properties, $add);
    $default_properties = array_unique($default_properties);
    sort($default_properties);
    $config->set("schema_types.default_properties.$schema_type", $default_properties)
      ->save();
  }

}
