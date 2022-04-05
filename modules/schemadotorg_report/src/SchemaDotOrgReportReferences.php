<?php

namespace Drupal\schemadotorg_report;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Schema.org report references service.
 */
class SchemaDotOrgReportReferences implements SchemaDotOrgReportReferencesInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a SchemaDotOrgReportReferences object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cacheBackend) {
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences($type = NULL) {
    if ($cache = $this->cacheBackend->get(static::CACHE_ID)) {
      $references = $cache->data;
    }
    else {
      $config = $this->configFactory->get('schemadotorg_report.settings');

      $references = [
        'about' => [],
        'types' => [],
      ];

      $about = $config->get('about');
      foreach ($about as $uri) {
        $references['about'][$uri] = $this->getRemoteUriTitle($uri);
      }

      $types = $config->get('types');
      foreach ($types as $schema_type => $uris) {
        foreach ($uris as $uri) {
          $references['types'][$schema_type][$uri] = $this->getRemoteUriTitle($uri);
        }
      }

      $this->cacheBackend->set(static::CACHE_ID, $references);
    }

    if ($type) {
      return $references['types'][$type] ?? [];
    }
    else {
      return $references;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetReferences() {
    $this->cacheBackend->delete(static::CACHE_ID);
  }

  /**
   * Get a remote URI's page title.
   *
   * @param string $uri
   *   A remote URI.
   *
   * @return string
   *   A remote URI's page title.
   */
  protected function getRemoteUriTitle($uri) {
    $contents = file_get_contents($uri);
    $dom = new \DOMDocument();
    @$dom->loadHTML($contents);
    $title_node = $dom->getElementsByTagName('title');
    $title = $title_node->item(0)->nodeValue;
    [$title] = preg_split('/\s*\|\s*/', $title);
    return $title;
  }

}
