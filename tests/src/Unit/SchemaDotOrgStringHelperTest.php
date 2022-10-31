<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Unit\SchemaDotOrgSchemaTypeBuilderTest;

use Drupal\schemadotorg\Utility\SchemaDotOrgElementHelper;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper
 * @group schemadotorg
 */
class SchemaDotOrgStringHelperTest extends UnitTestCase {

  /**
   * Tests SchemaDotOrgStringHelper::getFirstSentence().
   *
   * @covers ::getFirstSentence
   */
  public function testsGetFirstSentence(): void {
    $this->assertEquals(
      'This is a test.',
      SchemaDotOrgStringHelper::getFirstSentence('This is a test. This is a test.')
    );

    $this->assertEquals(
      'A specific question - e.g. from a user seeking answers online, or collected in a Frequently Asked Questions (FAQ) document.',
      SchemaDotOrgStringHelper::getFirstSentence('A specific question - e.g. from a user seeking answers online, or collected in a Frequently Asked Questions (FAQ) document.')
    );
  }

}
