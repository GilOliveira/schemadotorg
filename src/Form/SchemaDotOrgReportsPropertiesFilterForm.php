<?php

namespace Drupal\schemadotorg\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org reports type filter form.
 */
class SchemaDotOrgReportsPropertiesFilterForm extends SchemaDotOrgReportsFilterFormBase {

  /**
   * {@inheritdoc}
   */
  protected $table = 'properties';

  /**
   * {@inheritdoc}
   */
  protected $name = 'property';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->label = $instance->t('property');
    return $instance;
  }

}
