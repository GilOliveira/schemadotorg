<?php

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org installer service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgInstaller
 * @group schemadotorg
 */
class SchemaDotOrgInstallerTest extends SchemaDotOrgKernelTestBase {

  /**
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrginstallerInterface
   */
  protected $installer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installer = $this->container->get('schemadotorg.installer');
  }

  /**
   * Tests SchemaDotOrgInstallerInterface::requirements().
   *
   * @covers ::requirements
   */
  public function testRequirements() {
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Schema.org Blueprints: Recommended modules missing', $requirements['schemadotorg_modules']['title']);
  }

}
