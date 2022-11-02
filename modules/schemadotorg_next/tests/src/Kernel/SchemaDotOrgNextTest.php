<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_next\Kernel;

use Drupal\next\Entity\NextEntityTypeConfig;
use Drupal\next\Entity\NextSite;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Next.js integration.
 *
 * @covers schemadotorg_next_schemadotorg_mapping_insert()
 * @covers schemadotorg_next_node_type_delete()
 * @group schemadotorg
 */
class SchemaDotOrgNextTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'next',
    'schemadotorg_next',
  ];

  /**
   * Test Schema.org next.
   */
  public function testNext(): void {
    // Create Next.js site.
    $next_site = NextSite::create([
      'label' => 'Next.js site',
      'id' => 'next_site',
      'base_url' => 'https://next_site.com',
      'preview_url' => 'https://next_site/api/preview',
      'preview_secret' => 'secret',
    ]);
    $next_site->save();

    // Create Schema.org node:place.
    $this->createSchemaEntity('node', 'Place');

    // Check that Next.js entity type config was created.
    /** @var \Drupal\next\Entity\NextEntityTypeConfigInterface $next_entity_type_config */
    $next_entity_type_config = NextEntityTypeConfig::load('node.place');
    $configuration = $next_entity_type_config->getConfiguration();
    $this->assertEquals('node.place', $next_entity_type_config->id());
    $this->assertEquals('site_selector', $next_entity_type_config->getSiteResolver()->getId());
    $this->assertEquals(['next_site' => 'next_site'], $configuration['sites']);

    // Check that the Next.js entity type is deleted when its node type
    // dependency is deleted.
    NodeType::load('place')->delete();
    $this->assertNull(NextEntityTypeConfig::load('node.place'));
  }

}
