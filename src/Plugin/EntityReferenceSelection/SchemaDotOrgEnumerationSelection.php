<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

/**
 * Select entities using the field's mapping Schema.org property.
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg_enumeration",
 *   label = @Translation("Scheme.org enumeration"),
 *   group = "schemadotorg_enumeration",
 *   entity_types = {"taxonomy_term"},
 *   weight = 0
 * )
 */
class SchemaDotOrgEnumerationSelection extends SchemaDotOrgSelectionBase {

}
