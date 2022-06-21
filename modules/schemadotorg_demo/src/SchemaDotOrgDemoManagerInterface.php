<?php

namespace Drupal\schemadotorg_demo;

/**
 * Schema.org demo manager interface.
 */
interface SchemaDotOrgDemoManagerInterface {

  /**
   * Setup the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @return array
   *   An array of messages.
   */
  public function setup($name);

  /**
   * Teardown the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @return array
   *   An array of messages.
   */
  public function teardown($name);

  /**
   * Generate the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   */
  public function generate($name);

  /**
   * Kill the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   */
  public function kill($name);

  /**
   * Get demo types from demo name.
   *
   * @param string $name
   *   Demo name.
   * @param bool $required
   *   Include required types.
   *
   * @return array
   *   Demo types.
   */
  public function getTypes($name, $required = FALSE);

  /**
   * Detemine if a Schema.org demo is already setup.
   *
   * @param string $name
   *   A demo name.
   *
   * @return bool
   *   If a Schema.org demo is already setup.
   */
  public function isSetup($name);

}
