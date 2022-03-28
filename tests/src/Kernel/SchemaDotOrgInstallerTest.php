<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the installation of the Schema.org module.
 *
 * @covers \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::createTypeVocabulary
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgInstaller
 * @group schemadotorg
 */
class SchemaDotOrgInstallerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'field',
    'text',
    'taxonomy',
    'schemadotorg',
  ];

  /**
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface
   */
  protected $installer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('schemadotorg_mapping_type');

    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();
  }

  /**
   * Test Schema.org installed called via schemadotorg_install().
   */
  public function testInstaller() {
    $connection = Database::getConnection();

    // Check Schema.org types and properties table totals.
    $totals = [
      'types' => 1329,
      'properties' => 1442,
    ];
    foreach ($totals as $table => $total) {
      $count = $connection->select('schemadotorg_' . $table)
        ->fields('schemadotorg_' . $table, ['id'])
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals($total, $count);
    }

    // Check that Schema.org: Thing vocabulary exists.
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load('schema_thing');
    $this->assertNotEmpty($vocabulary);

    // Get Schema.org: Thing vocabulary terms by Schema.org type,
    $terms_by_type = $this->getTermsByType();

    // Check that Schema.org: Thing terms exist.
    $this->assertArrayHasKey('Thing', $terms_by_type);
    $this->assertArrayHasKey('Place', $terms_by_type);
    $this->assertArrayHasKey('Organization', $terms_by_type);
    $this->assertArrayHasKey('LocalBusiness', $terms_by_type);

    // Check that Schema.org: Thing vocabulary hierarchy.
    $this->assertEquals(
      [
        ['target_id' => $terms_by_type['Thing']->id()],
      ],
      $terms_by_type['Place']->parent->getValue()
    );
    $this->assertEquals(
      [
        ['target_id' => $terms_by_type['Thing']->id()],
      ],
      $terms_by_type['Organization']->parent->getValue()
    );
    $this->assertEquals(
      [
        ['target_id' => $terms_by_type['Organization']->id()],
        ['target_id' => $terms_by_type['Place']->id()],
      ],
      $terms_by_type['LocalBusiness']->parent->getValue()
    );

    // Unset LocalBusiness parents.
    $terms_by_type['LocalBusiness']->parent->setValue([]);
    $terms_by_type['LocalBusiness']->save();
    $this->assertEquals([['target_id' => 0]], $terms_by_type['LocalBusiness']->parent->getValue());

    // Delete Organization.
    $terms_by_type['Organization']->delete();

    // Store the Organization and LocalBusiness tids.
    $organization_tid = $terms_by_type['Organization']->id();
    $local_business_tid = $terms_by_type['LocalBusiness']->id();

    // Update the Schema.org module.
    $this->installer->install();
    $terms_by_type = $this->getTermsByType();

    // Check that update restores deleted terms.
    $this->assertArrayHasKey('Thing', $terms_by_type);
    $this->assertArrayHasKey('Place', $terms_by_type);
    $this->assertArrayHasKey('Organization', $terms_by_type);
    $this->assertArrayHasKey('LocalBusiness', $terms_by_type);

    // Check that reinstalling/updatings resets hierarchy.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller::updateTypeVocabularies
    $this->assertEquals(
      [
        ['target_id' => $terms_by_type['Organization']->id()],
        ['target_id' => $terms_by_type['Place']->id()],
      ],
      $terms_by_type['LocalBusiness']->parent->getValue()
    );

    // Check that Organization type has been recreated with new tid.
    $this->assertNotEquals($organization_tid, $terms_by_type['Organization']->id());

    // Check that LocalBusiness type has not been recreated with new tid.
    $this->assertEquals($local_business_tid, $terms_by_type['LocalBusiness']->id());
  }

  /**
   * Gets Schema.org: Thing terms by Schema.org type.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Schema.org: Thing terms by Schema.org type.
   */
  protected function getTermsByType() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $entity_type_manager->getStorage('taxonomy_term');
    $term_storage->resetCache();

    /** @var \Drupal\taxonomy\TermInterface[] $terms_by_type */
    $terms_by_type = [];
    $terms = $term_storage->loadByProperties(['vid' => 'schema_thing']);
    foreach ($terms as $term) {
      $terms_by_type[$term->schema_type->value] = $term;
    }
    return $terms_by_type;
  }

}
