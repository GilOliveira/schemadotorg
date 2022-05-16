<?php

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the functionality of the Schema.org settings element.
 *
 * @covers \Drupal\schemadotorg\Element\SchemaDotOrgSettings
 * @group schemadotorg
 */
class SchemaDotOrgSettingsElementTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_settings_element_test'];

  /**
   * Test Schema.org settings form.
   */
  public function testSchemaDotOrgSettingsElement() {
    $assert_session = $this->assertSession();

    // Check expected values when submittings the form.
    $this->drupalGet('/schemadotorg-settings-element-test');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("indexed:
  - one
  - two
  - three
indexed_grouped:
  A:
    - one
    - two
    - three
  B:
    - four
    - five
    - six
indexed_grouped_named:
  A:
    label: 'Group A'
    items:
      - one
      - two
      - three
  B:
    label: 'Group B'
    items:
      - four
      - five
      - six
associative:
  one: One
  two: Two
  three: Three
associative_grouped:
  A:
    one: One
    two: Two
    three: Three
  B:
    four: Four
    five: Five
    six: Six
associative_grouped_named:
  A:
    label: 'Group A'
    items:
      one: One
      two: Two
      three: Three
  B:
    label: 'Group B'
    items:
      four: Four
      five: Five
      six: Six
links:
  -
    uri: 'https://yahoo.com'
    title: Yahoo!!!
  -
    uri: 'https://google.com'
    title: Google
links_grouped:
  A:
    -
      uri: 'https://yahoo.com'
      title: Yahoo!!!
  B:
    -
      uri: 'https://google.com'
      title: Google");
  }

}
