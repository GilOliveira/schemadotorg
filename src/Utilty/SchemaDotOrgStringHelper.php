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
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', '_$0', $string);
    $snake_case = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1_$2', $intermediate);
    return strtolower($snake_case);
  }

  /**
   * Convert camel case (camelCase) to title case (Title Case).
   *
   * @param string $string
   *   A camel case string.
   *
   * @return string
   *   The camel case string converted to title case.
   *
   * @see https://gist.github.com/justjkk/1402061
   */
  public static function camelCaseToTitleCase($string) {
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $string);
    $title = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

    // Custom.
    $custom = [
      'Nonprofit501' => 'Nonprofit 501',
      'gtin' => 'GTIN',
      'rxcui' => 'RxCUI',
    ];
    foreach ($custom as $search => $replace) {
      $title = str_replace($search, $replace, $title);
    }

    // Abbreviations.
    $title = preg_replace_callback('/(\b)(cvd|id|isbn|isic|isrc|issn|iswc|iupac|lei|mpn|nsn|rsvp|sd|sha|sku|sms|vat|url|uri)(\b)/i', function ($matches) {
      return $matches[1] . strtoupper($matches[2]) . $matches[3];
    }, $title);

    // Minor words.
    $title = preg_replace_callback('/ (a|an|and|as|at|but|by|for|in|if|of|off|on|nor|not|or|per|so|the|to|up|via)(\b)/i', function ($matches) {
      return ' ' . strtolower($matches[1]) . $matches[2];
    }, $title);

    return ucfirst($title);
  }

  /**
   * Convert Schema.org type or property to Drupal machine name.
   *
   * @param string $label
   *   A Schema.org type or property.
   * @param int $length
   *   Maximum number of characters allowed for the Drupal machine name.
   *
   * @return string
   *   Schema.org type or property converted to Drupal machine name.
   *
   * @see https://www.allacronyms.com/
   */
  public static function toDrupalName($label, $length = 0) {
    $drupal_name = static::camelCaseToSnakeCase($label);

    // Custom.
    $custom = [
      'is_accessory_or_spare_part_for' => 'is_access_or_part_for',
      'is_located_in_subcellular_location' => 'is_located_in_subcell_loc',
      'offers_prescription_by_mail' => 'offers_prescript_by_mail',
      'customer_remorse_return_shipping_fees_amount' => 'cust_rem_ret_ship_fee_amt'
    ];
    if (isset($custom[$drupal_name])) {
      return $custom[$drupal_name];
    }

    /* ********************************************************************** */
    // DO NOT UPDATE ANY OF THE BELOW CODE.
    // Changing the below prefixes, suffixes, and abbreviations can break
    // existing fields.
    /* ********************************************************************** */

    // Prefixes.
    // Always apply prefixes so that abbreviated names are consistent.
    $prefixes = [
      'customer_remorse_return_' => 'cust_rem_ret_',
      'customer_' => 'cust_',
      'cvd_num_' => 'cvd_',
      'digital_document_' => 'digit_doc_',
      'government_' => 'gov_',
      'educational_occupational_' => 'edu_occ_',
      'eu_energy_efficiency_category' => 'eu_energy_eff_cat_',
      'energy_star_energy_efficiency_' => 'en_star_eff_',
      'food_establishment_' => 'food_est_',
      'item_defect_return_' => 'itm_def_ret_',
      'included_' => 'inc_',
      'includes_' => 'inc_',
      'number_of_available' => 'num_of_avail',
      'number_of_' => 'num_of_',
      'maximum_' => 'max_',
      'merchant_return_policy_' => 'merch_ret_pol_',
      'merchant_' => 'merch_',
      'medical_' => 'med_',
      'mission_' => 'mis_',
      'misconceptions_' => 'miscon_',
      'original_' => 'orig_',
      'verification_' => 'verif_',
      'return_' => 'ret_',
      'risks_or_complications_' => 'risks_or_comp_',
      'wearable_measurement_' => 'wear_measure_',
      'wearable_size_' => 'wear_size_',
    ];
    foreach ($prefixes as $search => $replace) {
      $drupal_name = preg_replace('/^' . $search . '/', $replace, $drupal_name);
    }

    // Do not do any more abbreviations if the name has less than two words.
    if (substr_count($drupal_name, '_') <= 1
      && (!$length || strlen($drupal_name) < $length)) {
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
      '_distribution' => '_dist',
      '_duration' => 'dur',
      '_education' => '_ed',
      '_buildings' => '_bld',
      '_configuration' => '_conf',
      '_enumeration' => '_enum',
      '_entity' => '_ent',
      '_specification' => '_spec',
      '_location' => '_loc',
      '_modulation' => '_mod',
      '_organization' => '_org',
      '_override' => '_over',
      '_process' => '_proc',
      '_processing' => '_proc',
      '_policy' => '_pol',
      '_reservation' => '_res',
      '_requirement' => '_req',
      '_registered' => '_reg',
      '_registration' => '_reg',
      '_responsibility' => '_resp',
      '_statistics' => '_stats',
      '_volume' => '_vol',
    ];
    foreach ($suffixes as $search => $replace) {
      $drupal_name = preg_replace('/' . $search . '$/', $replace, $drupal_name);
    }

    // Abbreviations.
    $abbreviations = [
      'health_insurance_plan' => 'hth_ins_plan',
      'health_plan' => 'hth_plan',
      'monoisotopic_molecular' => 'mono_molec',
      'updates_and_guidelines' => 'up_and_guide',
      'size_group' => 'size_grp',
      'shipping_fees' => 'ship_fees',
      'biological' => 'bio',
      'association' => 'assoc',
      'accessory' => 'access',
      'accommodation' => 'accom',
      'associated' => 'assoc',
      'attendance' => 'attend',
      'business' => 'biz',
      'document' => 'doc',
      'customer' => 'cust',
      'coinsurance' => 'coin',
      'consumption' => 'cons',
      'coverage' => 'cover',
      'credential' => 'cred',
      'defect' => 'def',
      'efficiency' => 'eff',
      'encoded' => 'enc',
      'enumeration' => 'enum',
      'experience' => 'exp',
      'frequency' => 'freq',
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
      'production' => 'prod',
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

  /**
   * Pluralize an English string.
   *
   * Reference:
   * - http://www.eval.ca/2007/03/03/php-pluralize-method/
   *
   * @param string $text
   *   English string that needs to be pluralized.
   * @param int $count
   *   The item count.
   *
   * @return string
   *   The pluralized string.
   */
  public static function pluralize($text, $count = 0) {
    if ($count == 1 || empty($text)) {
      return $text;
    }

    $length = strlen($text);
    $last_character = $text[$length - 1];

    if ($last_character == 's') {
      return $text;
    }

    if ($last_character == 'y') {
      return substr($text, 0, $length - 1) . 'ies';
    }

    return $text . 's';
  }

}
