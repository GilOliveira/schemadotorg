<?php

namespace Drupal\Tests\schemadotorg_jsonapi\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\schemadotorg_jsonapi\Breadcrumb\SchemaDotOrgJsonApiBreadcrumbBuilder
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock cache contexts manager serer which is used by the
    // breadcrumb builder.
    // @see \Drupal\Core\Breadcrumb\Breadcrumb
    // @see \Drupal\Core\Cache\RefinableCacheableDependencyTrait
    // @see \Drupal\Core\Cache\Cache
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    // Always return TRUE for ::assertValidTokens so that any cache context
    // will be accepted.
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    // Create a new container with the 'cache_contexts_manager'.
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests SchemaDotOrgJsonApiBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   SchemaDotOrgJsonApiBreadcrumbBuilder::applies() expected result.
   * @param string|null $route_name
   *   (optional) A route name.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies($expected, $route_name = NULL) {
    $breadcrumb_builder = $this->getMockBuilder('\Drupal\schemadotorg_jsonapi\Breadcrumb\SchemaDotOrgJsonApiBreadcrumbBuilder')
      ->onlyMethods([])
      ->getMock();

    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));

    $this->assertEquals($expected, $breadcrumb_builder->applies($route_match));
  }

  /**
   * Provides test data for testApplies().
   *
   * @return array
   *   Array of datasets for testApplies(). Structured as such:
   *   - SchemaDotOrgJsonApiBreadcrumbBuilder::applies() expected result.
   *   - SchemaDotOrgJsonApiBreadcrumbBuilder::applies() route name.
   */
  public function providerTestApplies() {
    return [
      [FALSE],
      [FALSE, 'schemadotorg'],
      [TRUE, 'schemadotorg_jsonapi.settings'],
    ];
  }

  /**
   * Tests ForumBreadcrumbBuilderBase::build().
   *
   * @see \Drupal\forum\Breadcrumb\ForumBreadcrumbBuilderBase::build()
   *
   * @covers ::build
   */
  public function testBuild() {
    // Build a breadcrumb builder to test.
    $breadcrumb_builder = $this->getMockBuilder('\Drupal\schemadotorg_jsonapi\Breadcrumb\SchemaDotOrgJsonApiBreadcrumbBuilder')
      ->onlyMethods([])
      ->getMock();

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // Check the breadcrumb links.
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $breadcrumb = $breadcrumb_builder->build($route_match);
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Structure', 'system.admin_structure'),
      Link::createFromRoute('Schema.org', 'entity.schemadotorg_mapping.collection'),
    ];
    $this->assertEquals($expected, $breadcrumb->getLinks());

    // Check the breadcrumb cache contexts.
    $this->assertEquals(['route'], $breadcrumb->getCacheContexts());

    // Check the breadcrumb cache max-age.
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

}