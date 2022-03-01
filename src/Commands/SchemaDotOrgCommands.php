<?php

namespace Drupal\schemadotorg\Commands;

use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {

  /**
   * Update Schema.org data and taxonomy.
   *
   * @usage schemadotorg:update
   *   Usage description
   *
   * @command schemadotorg:update
   * @aliases soup
   */
  public function update() {
    if (!$this->io()->confirm(dt('Are you sure you want to update Schema.org data and taxonomy?'))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = \Drupal::service('schemadotorg.installer');
    $installer->install();
    $this->output()->writeln(dt('Schema.org data and taxonomy updated.'));
  }

}
