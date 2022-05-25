<?php

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the functionality of the Schema.org settings form.
 *
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgSettingsPropertiesForm
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgSettingsNamesForm
 * @group schemadotorg
 */
class SchemaDotOrgSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org settings form.
   */
  public function testSettingsForm() {
    $this->assertSaveSettingsConfigForm('schemadotorg.settings', '/admin/structure/schemadotorg/settings');
    $this->assertSaveSettingsConfigForm('schemadotorg.settings', '/admin/structure/schemadotorg/settings/properties');
    $this->assertSaveSettingsConfigForm('schemadotorg.settings', '/admin/structure/schemadotorg/settings/names');
  }

}
