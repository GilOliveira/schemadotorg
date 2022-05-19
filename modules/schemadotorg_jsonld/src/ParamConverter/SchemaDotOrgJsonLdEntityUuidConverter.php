<?php

namespace Drupal\schemadotorg_jsonld\ParamConverter;

use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\schemadotorg_jsonld\Routing\SchemaDotOrgJsonLdRoutes;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity UUIDs to full objects.
 *
 * Copied from the JSON:API module.
 *
 * @see \Drupal\jsonapi\ParamConverter\EntityUuidConverter
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\Core\ParamConverter\EntityConverter
 *
 * @todo Remove when https://www.drupal.org/node/2353611 lands.
 */
class SchemaDotOrgJsonLdEntityUuidConverter extends EntityConverter {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Injects the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager to get the current content language.
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $uuid_key = $this->entityTypeManager->getDefinition($entity_type_id)
      ->getKey('uuid');
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      if (!$entities = $storage->loadByProperties([$uuid_key => $value])) {
        return NULL;
      }
      $entity = reset($entities);
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
        // @see https://www.drupal.org/project/drupal/issues/2624770
        $entity = $this->entityRepository->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
      }
      return $entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (
      $route->getDefault(SchemaDotOrgJsonLdRoutes::JSONLD_ROUTE_FLAG_KEY) &&
      !empty($definition['type']) && strpos($definition['type'], 'entity') === 0
    );
  }

}
