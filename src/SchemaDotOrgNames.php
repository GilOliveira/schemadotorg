<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org names service.
 *
 * @see https://www.allacronyms.com/
 */
class SchemaDotOrgNames implements SchemaDotOrgNamesInterface {

  /**
   * {@inheritdoc}
   */
  public function getNameMaxLength($table) {
    return ($table === 'types') ? 32 : 25;
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToSnakeCase($string) {
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', '_$0', $string);
    $snake_case = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1_$2', $intermediate);
    return strtolower($snake_case);
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToTitleCase($string) {
    // CamelCase to Title Case PHP Regex.
    // @see https://gist.github.com/justjkk/1402061
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $string);
    $title = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

    // Custom.
    $custom_titles = [
      'Nonprofit501' => 'Nonprofit 501',
      'gtin' => 'GTIN',
      'rxcui' => 'RxCUI',
    ];
    foreach ($custom_titles as $search => $replace) {
      $title = str_replace($search, $replace, $title);
    }

    // Acronyms.
    $acronyms = $this->getAcronyms();
    $title = preg_replace_callback('/(\b)(' . implode('|', $acronyms) . ')(\b)/i', function ($matches) {
      return $matches[1] . strtoupper($matches[2]) . $matches[3];
    }, $title);

    // Minor words.
    $minor_words = $this->getMinorWords();
    $title = preg_replace_callback('/ (' . implode('|', $minor_words) . ')(\b)/i', function ($matches) {
      return ' ' . strtolower($matches[1]) . $matches[2];
    }, $title);

    return ucfirst($title);
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToSentenceCase($string) {
    $sentence = $this->camelCaseToTitleCase($string);
    $sentence = preg_replace_callback('/ ([A-Z])([a-z])/', function ($matches) {
      return ' ' . strtolower($matches[1]) . $matches[2];
    }, $sentence);
    return ucfirst($sentence);
  }

  /**
   * {@inheritdoc}
   */
  public function toDrupalLabel($table, $string) {
    return ($table === 'types')
      ? $this->camelCaseToTitleCase($string)
      : $this->camelCaseToSentenceCase($string);
  }

  /**
   * {@inheritdoc}
   */
  public function toDrupalName($table, $string) {
    $length = $this->getNameMaxLength($table);
    $drupal_name = $this->camelCaseToSnakeCase($string);

    // Custom.
    $custom = $this->getCustomNames();
    if (isset($custom[$drupal_name])) {
      return $custom[$drupal_name];
    }

    // Prefixes.
    $prefixes = $this->getNamePrefixes();
    foreach ($prefixes as $search => $replace) {
      $drupal_name = preg_replace('/^' . $search . '_/', $replace . '_', $drupal_name);
    }

    // Do not do any more abbreviations if the name has less than two words.
    if (substr_count($drupal_name, '_') <= 1
      && (!$length || (strlen($drupal_name) < $length))) {
      return $drupal_name;
    }

    // Suffixes.
    $suffixes = $this->getNameSuffixes();
    foreach ($suffixes as $search => $replace) {
      $drupal_name = preg_replace('/_' . $search . '$/', '_' . $replace, $drupal_name);
    }

    // Do not do any more abbreviations if the name is less than the limit.
    if (!$length || strlen($drupal_name) < $length) {
      return $drupal_name;
    }

    // Abbreviations.
    $abbreviations = $this->getNameAbbreviations();
    foreach ($abbreviations as $search => $replace) {
      $drupal_name = preg_replace('/(^|_)' . $search . '($|_)/', '\1' . $replace . '\2', $drupal_name);
    }

    return $drupal_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getNamePrefixes() {
    return [
      'body_measurement' => 'body_meas',
      'customer_remorse_return' => 'cust_rem_ret',
      'customer' => 'cust',
      'cvd_num' => 'cvd',
      'digital_document' => 'digit_doc',
      'educational_occupational' => 'edu_occ',
      'educational' => 'edu',
      'eu_energy_efficiency_category' => 'eu_energy_eff_cat',
      'energy_star_energy_efficiency' => 'en_star_eff',
      'food_establishment' => 'food_est',
      'item_defect_return' => 'itm_def_ret',
      'included' => 'inc',
      'includes' => 'inc',
      'number_of_available' => 'num_of_avail',
      'number' => 'num',
      'maximum' => 'max',
      'merchant_return_policy' => 'mer_ret_pol',
      'merchant' => 'mer',
      'medical' => 'med',
      'mission' => 'mis',
      'misconceptions' => 'miscon',
      'original' => 'orig',
      'verification' => 'ver',
      'reservation' => 'res',
      'return_label' => 'ret_lbl',
      'return_fees' => 'ret_fees',
      'risks_or_complications' => 'risks_or_comp',
      'suggested' => 'sug',
      'wearable_measurement' => 'wear_measure',
      'wearable_size' => 'wear_size',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getNameSuffixes() {
    return [
      'ascending' => 'asc',
      'audience' => 'aud',
      'aspect' => 'asp',
      'amount' => 'amt',
      'business' => 'biz',
      'capacity' => 'cap',
      'category' => 'cat',
      'composition' => 'comp',
      'credential' => 'cred',
      'descending' => 'dsc',
      'description' => 'desc',
      'distribution' => 'dist',
      'duration' => 'dur',
      'education' => 'ed',
      'buildings' => 'bld',
      'configuration' => 'conf',
      'enumeration' => 'enum',
      'entity' => 'ent',
      'establishment' => 'est',
      'specification' => 'spec',
      'language' => 'lang',
      'location' => 'loc',
      'modulation' => 'mod',
      'organization' => 'org',
      'override' => 'over',
      'process' => 'proc',
      'processing' => 'proc',
      'procedure' => 'proc',
      'policy' => 'pol',
      'recommendation' => 'rec',
      'reservation' => 'res',
      'requirement' => 'req',
      'requirements' => 'req',
      'registered' => 'reg',
      'registration' => 'reg',
      'responsibility' => 'resp',
      'standard' => 'stand',
      'statistics' => 'stats',
      'volume' => 'vol',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getNameAbbreviations() {
    return [
      'health_insurance_plan' => 'hth_ins_plan',
      'health_plan' => 'hth_plan',
      'monoisotopic_molecular' => 'mono_molec',
      'updates_and_guidelines' => 'up_and_guide',
      'size_group' => 'size_grp',
      'shipping_fees' => 'ship_fees',
      'biological' => 'bio',
      'association' => 'assoc',
      'apartment' => 'apt',
      'accessory' => 'access',
      'accommodation' => 'accom',
      'amount' => 'amt',
      'associated' => 'assoc',
      'attendance' => 'attend',
      'availability' => 'avail',
      'business' => 'biz',
      'document' => 'doc',
      'customer' => 'cust',
      'coinsurance' => 'coin',
      'condition' => 'cond',
      'configuration' => 'conf',
      'consumption' => 'cons',
      'composition' => 'comp',
      'coverage' => 'cover',
      'credential' => 'cred',
      'defect' => 'def',
      'efficiency' => 'eff',
      'encoded' => 'enc',
      'entertainment' => 'ent',
      'employer' => 'emp',
      'employment' => 'emp',
      'establishment' => 'est',
      'enumeration' => 'enum',
      'experience' => 'exp',
      'government' => 'gov',
      'frequency' => 'freq',
      'identification' => 'id',
      'included' => 'inc',
      'includes' => 'inc',
      'incorporated' => 'inc',
      'insurance' => 'ins',
      'hours' => 'hrs',
      'language' => 'lang',
      'minutes' => 'min',
      'measurement' => 'measure',
      'medical' => 'med',
      'membership' => 'mem',
      'minimum' => 'min',
      'maximum' => 'max',
      'number' => 'num',
      'observational' => 'obs',
      'occupational' => 'occ',
      'organization' => 'org',
      'pregnancy' => 'preg',
      'permission' => 'perm',
      'process' => 'proc',
      'procedure' => 'proc',
      'production' => 'prod',
      'publication' => 'pub',
      'reference' => 'ref',
      'requirement' => 'req',
      'requirements' => 'req',
      'return' => 'ret',
      'specification' => 'spec',
      'sequence' => 'seq',
      'suggested' => 'sug',
      'transport' => 'trans',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomNames() {
    return [
      'applies_to_delivery_method' => 'applies_to_delivery_meth',
      'customer_remorse_return_shipping_fees_amount' => 'cust_rem_ret_ship_fee_amt',
      'is_accessory_or_spare_part_for' => 'is_access_or_part_for',
      'is_located_in_subcellular_location' => 'is_located_in_subcell_loc',
      'offers_prescription_by_mail' => 'offers_prescript_by_mail',
      'provides_broadcast_service' => 'provides_broadcast_serv',
      'public_transport_closures_info' => 'pub_trans_closures_info',
      'vehicle_identification_number' => 'vin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomTitles() {
    return [
      'Nonprofit501' => 'Nonprofit 501',
      'gtin' => 'GTIN',
      'rxcui' => 'RxCUI',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAcronyms() {
    return ['cvd', 'id', 'isbn', 'isic', 'isrc', 'issn', 'iswc', 'iupac', 'lei', 'mpn', 'nsn', 'rsvp', 'sd', 'sha', 'sku', 'sms', 'vat', 'url', 'uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMinorWords() {
    return ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'if', 'of', 'off', 'on', 'nor', 'not', 'or', 'per', 'so', 'the', 'to', 'up', 'via'];
  }

}
