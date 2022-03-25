<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Schema.org mapping admin listing page.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgListBuilder
 * @group schemadotorg
 */
class SchemaDotOrgMappingListBuilderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['schemadotorg'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
  }

  /**
   * Tests that the correct cache contexts are set.
   */
  public function testCacheContexts() {
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity_type.manager')->getListBuilder('schemadotorg_mapping');

    $build = $list_builder->render();
    $this->container->get('renderer')->renderRoot($build);

    $this->assertEqualsCanonicalizing(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'url.query_args.pagers:0', 'user.permissions'], $build['#cache']['contexts']);
  }

}
