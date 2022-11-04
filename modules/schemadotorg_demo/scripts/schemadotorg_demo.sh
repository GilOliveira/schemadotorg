#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg_demo.sh";
  echo;
  echo "Installs a demo of the Schema.org Blueprints module.";
  echo;
  echo "This scripts assumes you are starting with a plain vanilla standard instance of Drupal.";
  echo;
  echo "The below commands should be executed from the root of your Drupal installation.";
  echo;
  echo "Usage:"
  echo;
  IFS=$'\n'
  for f in $(declare -F); do
    function_name=${f:11}
    if [[ ! $function_name =~ ^(help|function_exists)$ ]]; then
      echo "./schemadotorg_demo.sh $function_name";
    fi
  done
}

function install() {
  local profile=${1:-"standard"};
  local email=`git config user.email`;

  echo "Installing $profile profile";
  drush -yv site-install --account-mail="$email"\
    --account-name="demo"\
    --account-pass="demo"\
    --site-mail="$email"\
    --site-name="Schema.org Blueprints Demo"\
    $profile;

  drush -y config-set system.site slogan 'A demo of the Schema.org Blueprints module for Drupal.';
}

function install_base() {
  install;
  drush -y pm:enable schemadotorg\
    schemadotorg_ui\
    schemadotorg_descriptions\
    schemadotorg_mapping_set\
    schemadotorg_report\
    schemadotorg_subtype\
    schemadotorg_export\
    schemadotorg_media\
    schemadotorg_paragraphs\
    schemadotorg_taxonomy\
    schemadotorg_devel;
}

function install_apis() {
  install_base;
  drush -y pm:enable schemadotorg_jsonapi\
    schemadotorg_jsonapi_preview\
    schemadotorg_jsonld\
    schemadotorg_jsonld_breadcrumb\
    schemadotorg_jsonld_embed\
    schemadotorg_jsonld_endpoint\
    schemadotorg_jsonld_preview;
}

function install_extras() {
  install_apis;
  drush -y pm:enable schemadotorg_action\
    schemadotorg_auto_entitylabel\
    schemadotorg_content_moderation\
    schemadotorg_field_group\
    schemadotorg_focal_point\
    schemadotorg_inline_entity_form\
    schemadotorg_layout_paragraphs\
    schemadotorg_metatag\
    schemadotorg_office_hours\
    schemadotorg_scheduler\
    schemadotorg_sidebar\
    schemadotorg_simple_sitemap\
    schemadotorg_smart_date;
}

function install_experimental() {
  drush -y pm:enable
    schemadotorg_flexfield;
}

function install_next() {
  install_extras;
  drush -y pm:enable schemadotorg_next\
    schemadotorg_next_components;
}

################################################################################

function install_demo_standard() {
  install;
  drush -y pm:enable schemadotorg_demo_standard;
}

function install_demo_standard_next() {
  install_demo_standard;
  drush -y pm:enable schemadotorg_demo_next;
}

function install_demo_standard_translation() {
  install_demo_standard;
  drush -y pm:enable schemadotorg_demo_standard_translation;
}

function install_demo_standard_translation_umami() {
  install_demo_standard_translation

  drush locale:check;
  drush locale:update;
  drush -y pm:enable schemadotorg_demo_umami_content;
}

################################################################################

function_name=$1; shift;

if function_exists $function_name; then

  $function_name $@;

else

  if [[ ! -z "$function_name" ]]; then
    echo "Function to '$function_name' does not exist.";
    echo;
  fi

  help;

fi
