<?php

namespace Drupal\schemadotorg\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org reports property filter form.
 */
class SchemaDotOrgReportsTypesFilterForm extends SchemaDotOrgReportsFilterFormBase {

  /**
   * {@inheritdoc}
   */
  protected $table = 'types';

  /**
   * {@inheritdoc}
   */
  protected $name = 'type';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->label = $instance->t('type');
    return $instance;
  }

}
