<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Unit\SchemaDotOrgSchemaTypeBuilderTest;

use Drupal\schemadotorg\Utility\SchemaDotOrgElementHelper;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * @coversDefaultClass \Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper
 * @group schemadotorg
 */
class SchemaDotOrgStringHelperTest extends UnitTestCase {

  /**
   * Tests SchemaDotOrgStringHelper::getFirstSentence().
   *
   * @param string $string
   *   The string to run through SchemaDotOrgStringHelper::getFirstSentence().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see SchemaDotOrgStringHelper::getFirstSentence()
   *
   * @dataProvider providerGetFirstSentence
   */
  public function testGetFirstSentence(string $string, string $expected): void {
    $result = SchemaDotOrgStringHelper::getFirstSentence($string);
    $this->assertEquals($expected, $result, serialize($string));
  }

  /**
   * Data provider for testGetFirstSentence().
   *
   * @see testGetFirstSentence()
   */
  public function providerGetFirstSentence(): array {
    $tests = [];
    $tests[] = [
      'This is a test. This is a test.',
      'This is a test.',
    ];
    $tests[] = [
      'A specific question - e.g. from a user seeking answers online, or collected in a Frequently Asked Questions (FAQ) document.',
      'A specific question - e.g. from a user seeking answers online, or collected in a Frequently Asked Questions (FAQ) document.',
    ];
    $tests[] = [
      'A description of the job location (e.g. TELECOMMUTE for telecommute jobs).',
      'A description of the job location (e.g. TELECOMMUTE for telecommute jobs).',
    ];
    $tests[] = [
      'Event type: Exhibition event, e.g. at a museum, library, archive, tradeshow, ...',
      'Event type: Exhibition event, e.g. at a museum, library, archive, tradeshow, ...',
    ];
    $tests[] = [
      'Text representing an XPath (typically but not necessarily version 1.0).',
      'Text representing an XPath (typically but not necessarily version 1.0).',
    ];
    return $tests;
  }

}
