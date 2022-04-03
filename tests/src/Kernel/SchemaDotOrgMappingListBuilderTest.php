<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the Schema.org mapping admin listing page.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgListBuilder
 * @group schemadotorg
 */
class SchemaDotOrgMappingListBuilderTest extends SchemaDotOrgKernelTestBase {

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
