<?php

namespace Drupal\Tests\schemadotorg_report\Unit\Breadcrumb;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\schemadotorg_report\Breadcrumb\SchemaDotOrgReportBreadcrumbBuilder
 * @group schemadotorg
 */
class SchemaDotOrgReportBreadcrumbBuilderTest extends UnitTestCase {

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
   * Tests SchemaDotOrgReportBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   SchemaDotOrgReportBreadcrumbBuilder::applies() expected result.
   * @param string|null $route_name
   *   (optional) A route name.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies($expected, $route_name = NULL) {
    $breadcrumb_builder = $this->getMockBuilder('\Drupal\schemadotorg_report\Breadcrumb\SchemaDotOrgReportBreadcrumbBuilder')
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
   *   - SchemaDotOrgReportBreadcrumbBuilder::applies() expected result.
   *   - SchemaDotOrgReportBreadcrumbBuilder::applies() route name.
   */
  public function providerTestApplies() {
    return [
      [FALSE],
      [FALSE, 'schemadotorg_reports'],
      [TRUE, 'schemadotorg_reports.page'],
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
    $breadcrumb_builder = $this->getMockBuilder('\Drupal\schemadotorg_report\Breadcrumb\SchemaDotOrgReportBreadcrumbBuilder')
      ->onlyMethods([])
      ->getMock();

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $breadcrumb_builder->setStringTranslation($translation_manager);

    // Build the breadcrumb with a mock route..
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $breadcrumb = $breadcrumb_builder->build($route_match);

    // Check the breadcrumb links.
    $expected = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Reports', 'system.admin_reports'),
      Link::createFromRoute('Schema.org', 'schemadotorg_reports'),
    ];
    $this->assertEquals($expected, $breadcrumb->getLinks());

    // Check the breacrumb cache contexts.
    $this->assertEquals(['route'], $breadcrumb->getCacheContexts());

    // Check the breacrumb cache max-age.
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

}
