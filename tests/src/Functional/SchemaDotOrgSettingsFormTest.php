<?php

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org settings form.
 *
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgSettingsGeneralForm
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
  public function testSchemaDotOrgSettingsForm() {
    $assert_session = $this->assertSession();

    // Check that editing and re-saving the settings does not alter the
    // expected values.
    $expected_data = \Drupal::configFactory()->get('schemadotorg.settings')->getRawData();
    $this->drupalGet('/admin/structure/schemadotorg/settings');
    $this->submitForm([], 'Save configuration');
    $assert_session->responseContains('The configuration options have been saved.');
    \Drupal::configFactory()->reset('schemadotorg.settings');
    $actual_data = \Drupal::configFactory()->get('schemadotorg.settings')->getRawData();
    $this->assertEquals($expected_data, $actual_data);

    // Check that editing and re-saving the names does not alter the
    // expected values.
    $expected_data = \Drupal::configFactory()->get('schemadotorg.settings')->getRawData();
    $this->drupalGet('/admin/structure/schemadotorg/settings/names');
    $this->submitForm([], 'Save configuration');
    $assert_session->responseContains('The configuration options have been saved.');
    \Drupal::configFactory()->reset('schemadotorg.settings');
    $actual_data = \Drupal::configFactory()->get('schemadotorg.settings')->getRawData();
    $this->assertEquals($expected_data, $actual_data);

  }

}
