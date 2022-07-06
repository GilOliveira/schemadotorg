#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg-demo.sh";
  echo;
  echo "Installs a demo of the Schema.org Blueprints module."
  echo;
  echo "This scripts assumes you are starting with a plain vanilla standard instance of Drupal."
  echo;
  echo "The below commands should be executed from the root of your Drupal installation."
  echo;
  echo "Usage:"
  echo;
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh help";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh install";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh import";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh export";
}

function status() {
  drush status;
}

function install() {
  echo "Installing Schema.org Standard Profile demo with core and contrib modules";

  EMAIL=`git config user.email`
  drush -yv site-install --account-mail="$EMAIL"\
    --account-name="demo"\
    --account-pass="demo"\
    --site-mail="$EMAIL"\
    --site-name="Schema.org Blueprints Demo";

  drush -y config-set system.site slogan 'A demo of the Schema.org Blueprints module for Drupal.'

  drush -y pm-enable schemadotorg_standard;
}

function translate() {
  drush -y pm-enable schemadotorg_standard_translation;
  drush -y pm-enable schemadotorg_translation;
  drush locale:check
  drush locale:update
  drush -y pm-enable schemadotorg_demo_umami_content;
}

function import() {
  drush features:import -y schemadotorg
  drush features:import -y schemadotorg_descriptions
  drush features:import -y schemadotorg_demo
  drush features:import -y schemadotorg_inline_entity_form
  drush features:import -y schemadotorg_jsonapi
  drush features:import -y schemadotorg_jsonapi_preview
  drush features:import -y schemadotorg_jsonld
  drush features:import -y schemadotorg_jsonld_endpoint
  drush features:import -y schemadotorg_jsonld_preview
  drush features:import -y schemadotorg_paragraphs
  drush features:import -y schemadotorg_report
  drush features:import -y schemadotorg_standard
  drush features:import -y schemadotorg_translate
  drush features:import -y schemadotorg_taxonomy
  # drush features:import -y schemadotorg_flexfield
}

function export() {
  drush features:export -y schemadotorg
  drush features:export -y schemadotorg_descriptions
  drush features:export -y schemadotorg_demo
  drush features:export -y schemadotorg_inline_entity_form
  drush features:export -y schemadotorg_jsonapi
  drush features:export -y schemadotorg_jsonapi_preview
  drush features:export -y schemadotorg_jsonld
  drush features:export -y schemadotorg_jsonld_endpoint
  drush features:export -y schemadotorg_jsonld_preview
  drush features:export -y schemadotorg_paragraphs
  drush features:export -y schemadotorg_report
  drush features:export -y schemadotorg_standard
  drush features:export -y schemadotorg_translate
  drush features:export -y schemadotorg_taxonomy
  # drush features:export -y schemadotorg_flexfield
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
