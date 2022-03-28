<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Schema.org autocomplete controller.
 *
 * @coversClass \Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteControllerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['schemadotorg'];

  /**
   * The Schema.org autocomplete controller.
   *
   * @var \Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installConfig(['schemadotorg']);

    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    $this->controller = SchemaDotOrgAutocompleteController::create($this->container);
  }

  /**
   * Test the Schema.org autocomplete controller.
   */
  public function testAutocompleteController() {
    // Check searching for 'Thing' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'types');
    $this->assertEquals('[{"value":"ClothingStore","label":"ClothingStore"},{"value":"MensClothingStore","label":"MensClothingStore"},{"value":"Thing","label":"Thing"}]', $result->getContent());

    // Check searching for 'MensClothingStore' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'MensClothingStore']), 'types');
    $this->assertEquals('[{"value":"MensClothingStore","label":"MensClothingStore"}]', $result->getContent());

    // Check searching for 'Thing' within Schema.org properies returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'properties');
    $this->assertEquals('[]', $result->getContent());

  }

}
