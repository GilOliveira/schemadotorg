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

    $this->setEventDefaultProperties(
      ['startDate', 'endDate', 'duration'],
      ['eventSchedule']
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
    $this->setEventDefaultProperties(
      ['eventSchedule'],
      ['startDate', 'endDate', 'duration'],
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
   * Update Event Schema.org default properties by removing and adding properties.
   *
   * @param array $remove
   *   Schema.org properties to be removed.
   * @param array $add
   *   Schema.org properties to be added.
   */
  protected function setEventDefaultProperties(array $remove, array $add): void {
    $config = $this->configFactory->getEditable('schemadotorg.settings');
    $event_properties = $config->get('schema_types.default_properties.Event');
    if (!$event_properties) {
      return;
    }

    $event_properties = array_filter($event_properties, function ($property) use ($remove) {
      return !in_array($property, $remove);
    });
    $event_properties = array_merge($event_properties, $add);
    $event_properties = array_unique($event_properties);
    asort($event_properties);
    $config->set('schema_types.default_properties.Event', $event_properties)
      ->save();
  }

}
