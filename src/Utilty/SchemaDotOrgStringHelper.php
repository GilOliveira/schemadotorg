<?php

namespace Drupal\schemadotorg\Utilty;

/**
 * Schema.org string helper methods.
 *
 * @see https://en.wikipedia.org/wiki/Naming_convention_(programming)#Examples_of_multiple-word_identifier_formats
 */
class SchemaDotOrgStringHelper {

  /**
   * Convert camel case (camelCase) to snake case (snake_case).
   *
   * @param string $string
   *   A camel case string.
   *
   * @return string
   *   The camel case string converted to snake case.
   */
  public static function camelCaseToSnakeCase($string) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', $string));
  }

  /**
   * Convert variable (camelCase or snake_case) to a label.
   *
   * @param string $string
   *   A variable.
   *
   * @return string
   *   Variable converted to text.
   */
  public static function toLabel($string) {
    $text = preg_replace('/([a-z])([A-Z])/', '\1_\2', $string);
    $text = str_replace('_', ' ', $text);
    $text = strtolower($text);
    return ucfirst($text);
  }

  /**
   * Convert Schema.org type or property to Drupal machine name.
   *
   * @param string $label
   *   A Schema.org type or property.
   *
   * @return string
   *   Schema.org type or property converted to Drupal machine name.
   *
   * @see https://www.allacronyms.com/,
   */
  public static function toDrupalName($label) {
    $drupal_name = static::camelCaseToSnakeCase($label);

    // Custom.
    $custom = [
      'is_accessory_or_spare_part_for' => 'is_accessory_or_part_for',
      'is_located_in_subcellular_location' => 'is_located_in_subcell_loc',
      'offers_prescription_by_mail' => 'offers_prescript_by_mail',
    ];
    if (isset($custom[$drupal_name])) {
      return $custom[$drupal_name];
    }

    // Prefixes.
    $prefixes = [
      'customer_remorse_return_' => 'cust_rem_ret_',
      'customer_' => 'cust_',
      'cvd_num_' => 'cvd_',
      'government_' => 'gov_',
      'euenergy_efficiency_category_' => 'euenergy_eff_cat_',
      'energy_star_energy_efficiency_' => 'energy_star_efficiency_',
      'number_of_available' => 'num_of_avail',
      'number_of_' => 'num_of_',
      'maximum_' => 'max_',
      'merchant_return_policy_' => 'merc_ret_pol_',
      'merchant_' => 'merc_',
      'medical_' => 'med_',
      'mission_' => 'mis_',
      'original_' => 'orig_',
      'verification_' => 'verif_',
      'return_' => 'ret_',
      'risks_or_complications_' => 'risks_or_comp_',
      'wearable_measurement_' => 'wear_measure_',
      'wearable_size_group_' => 'wear_size_grp',
    ];
    foreach ($prefixes as $search => $replace) {
      $drupal_name = preg_replace('/^' . $search . '/', $replace, $drupal_name);
    }

    // Do not replace suffixes and words if the name has less than two words.
    if (substr_count($drupal_name, '_') <= 1) {
      return $drupal_name;
    }

    // Suffixes.
    $suffixes = [
      '_ascending' => '_asc',
      '_amount' => '_amt',
      '_business' => '_biz',
      '_capacity' => '_cap',
      '_category' => '_cat',
      '_credential' => '_cred',
      '_descending' => '_dsc',
      '_description' => '_desc',
      '_duration' => 'dur',
      '_education' => '_ed',
      '_details' => '_det',
      '_buildings' => '_bld',
      '_configuration' => '_config',
      '_enumeration' => '_enum',
      '_specification' => '_spec',
      '_location' => '_loc',
      '_modulation' => '_mod',
      '_organization' => 'org',
      '_option' => '_opt',
      '_override' => '_over',
      '_process' => '_proc',
      '_policy' => '_pol',
      '_reservation' => '_res',
      '_requirement' => '_req',
      '_registered' => '_reg',
      '_registration' => '_reg',
      '_statistics' => '_stats',
      '_volume' => '_vol',
    ];
    foreach ($suffixes as $search => $replace) {
      $drupal_name = preg_replace('/' . $search . '$/', $replace, $drupal_name);
    }

    // Abbreviations.
    $abbreviations = [
      'health_insurance_plan' => 'hip',
      'health_plan' => 'hp',
      'health_aspect' => 'ha',
      'monoisotopic_molecular' => 'mm',
      'updates_and_guidelines' => 'up_and_guide',
      'size_group' => 'size_grp',
      'shipping_fees' => 'ship_fees',
      'biological' => 'bio',
      'association' => 'assoc',
      'accessory' => 'access',
      'accommodation' => 'accom',
      'business' => 'biz',
      'document' => 'doc',
      'customer' => 'cust',
      'consumption' => 'consumpt',
      'coverage' => 'cover',
      'credential' => 'cred',
      'defect' => 'def',
      'efficiency' => 'eff',
      'encoded' => 'enc',
      'enumeration' => 'enum',
      'experience' => 'exp',
      'identification' => 'id',
      'insurance' => 'ins',
      'hours' => 'hrs',
      'minutes' => 'min',
      'measurement' => 'measure',
      'minimum' => 'min',
      'maximum' => 'max',
      'number' => 'num',
      'organization' => 'org',
      'permission' => 'perm',
      'process' => 'proc',
      'publication' => 'pub',
      'requirements' => 'req',
      'return' => 'ret',
      'specification' => 'spec',
      'sequence' => 'seq',
      'transport' => 'trans',
    ];
    foreach ($abbreviations as $search => $replace) {
      $drupal_name = preg_replace('/(^|_)' . $search . '($|_)/', '\1' . $replace . '\2', $drupal_name);
    }

    return $drupal_name;
  }

}
