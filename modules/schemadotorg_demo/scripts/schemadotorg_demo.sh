#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg_demo.sh";
  echo;
  echo "Installs a demo of the Schema.org Blueprints module."
  echo;
  echo "This scripts assumes you are starting with a plain vanilla standard instance of Drupal."
}

function install() {
  local profile=${1:-"standard"}
  local email=`git config user.email`

  echo "Installing $profile profile";
  drush -yv site-install --account-mail="$email"\
    --account-name="demo"\
    --account-pass="demo"\
    --site-mail="$email"\
    --site-name="Schema.org Blueprints Demo"\
    $profile;

  drush -y config-set system.site slogan 'A demo of the Schema.org Blueprints module for Drupal.'
}

function install_base() {
  install

  # Base.
  drush -y pm:enable schemadotorg\
    schemadotorg_descriptions\
    schemadotorg_devel\
    schemadotorg_export\
    schemadotorg_mapping_set\
    schemadotorg_media\
    schemadotorg_paragraphs\
    schemadotorg_report\
    schemadotorg_subtype\
    schemadotorg_taxonomy\
    schemadotorg_ui;

  # JSON:API/JSON-LD.
  drush -y pm:enable schemadotorg_jsonapi\
    schemadotorg_jsonapi_preview\
    schemadotorg_jsonld\
    schemadotorg_jsonld_breadcrumb\
    schemadotorg_jsonld_embed\
    schemadotorg_jsonld_endpoint\
    schemadotorg_jsonld_preview;
}

function install_extras() {
  install
  drush -y pm:enable schemadotorg\
    schemadotorg_action\
    schemadotorg_auto_entitylabel\
    schemadotorg_content_moderation\
    schemadotorg_descriptions\
    schemadotorg_devel\
    schemadotorg_export\
    schemadotorg_field_group\
    schemadotorg_flexfield\
    schemadotorg_focal_point\
    schemadotorg_inline_entity_form\
    schemadotorg_jsonapi\
    schemadotorg_jsonapi_preview\
    schemadotorg_jsonld\
    schemadotorg_jsonld_breadcrumb\
    schemadotorg_jsonld_embed\
    schemadotorg_jsonld_endpoint\
    schemadotorg_jsonld_preview\
    schemadotorg_layout_paragraphs\
    schemadotorg_mapping_set\
    schemadotorg_media\
    schemadotorg_metatag\
    schemadotorg_office_hours\
    schemadotorg_paragraphs\
    schemadotorg_report\
    schemadotorg_scheduler\
    schemadotorg_sidebar\
    schemadotorg_simple_sitemap\
    schemadotorg_smart_date\
    schemadotorg_subtype\
    schemadotorg_taxonomy\
    schemadotorg_ui;
}

function install_demo_standard() {
  install
  drush -y pm:enable schemadotorg_demo_standard;
}

function install_demo_standard_translation() {
  install_demo_standard
  drush -y pm:enable schemadotorg_demo_standard_translation;
}

function install_demo_standard_next() {
  install_demo_standard
  drush -y pm:enable schemadotorg_demo_next;
}

function install_demo_standard_translation_umami() {
  install_demo_standard_translation

  drush locale:check
  drush locale:update
  drush -y pm:enable schemadotorg_demo_umami_content;
}

################################################################################

SCRIPT_DIRECTORY=`dirname "$0"`

function_name=$1; shift;

if function_exists $function_name; then
  $function_name $@;
else
  echo "Function to '$function_name' does not exist.";
  echo;
  help;
fi
