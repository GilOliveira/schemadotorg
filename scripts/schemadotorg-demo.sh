#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg-demo.sh";
  echo;
  echo "Download, installs, and configures a demo of the Schema.org Blueprints module."
  echo;
  echo "This scripts assumes you are starting with a plain vanilla standard instance of Drupal."
  echo;
  echo "The below commands should be executed from the root of your Drupal installation."
  echo;
  echo "Usage:"
  echo;
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh help";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh require";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh recommended";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh install";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh configure";
}

function status() {
  drush status;
}

function require() {
  echo "Adding composer dependencies";
  composer require drupal/address
  composer require drupal/admin_toolbar
  composer require drupal/anonymous_redirect
  composer require drupal/coffee
  composer require drupal/devel
  composer require drupal/features
  composer require drupal/field_group
  composer require drupal/gin
  composer require drupal/gin_login
  composer require drupal/inline_entity_form
  composer require drupal/jsonapi_extras
  composer require drupal/paragraphs
  composer require drupal/token
}

function recommended() {
  composer require drupal/gin_lb
  composer require drupal/layout_builder_modal
  composer require drupal/embed
  composer require drupal/entity_embed
  composer require drupal/entity_usage
  composer require drupal/inline_entity_form
  composer require drupal/flexfield
  composer require drupal/time_field
  composer require drupal/smart_date
  composer require drupal/range
  # composer require drupal/gender
  composer require drupal/address
  composer require drupal/phone_international
  composer require drupal/telephone_formatter
  composer require drupal/select_or_other
  composer require drupal/select_text_value
  composer require drupal/double_field
  composer require drupal/key_value_field
  composer require drupal/computed_field
  composer require drupal/field_token_value
}

function install() {
  echo "Installing Schema.org Standard Profile demo with core and contrib modules";
  drush -y pm-enable schemadotorg_standard;

  echo "Installing contrib modules";
  drush -y pm-enable \
    admin_toolbar\
    admin_toolbar_tools\
    anonymous_redirect\
    coffee\
    devel\
    devel_generate\
    features\
    webprofiler;
}

function configure() {
  echo "Configuring system settings";
  drush -y config-set system.logging error_level verbose
  drush -y config-set system.site name 'Schema.org Demo Site'
  drush -y config-set system.site slogan 'A demo of Schema.org integration with Drupal.'

  echo "Configuring administrative theme";
  drush theme:enable gin
  drush -y config-set system.theme default gin
  drush -y config-set system.theme admin gin
  drush -y config-set gin.settings classic_toolbar horizontal
  drush -y config-set gin.settings show_description_toggle 1
  drush -y config-set system.theme.global features.node_user_picture 0
  drush -y pm-enable gin_toolbar gin_login

  echo "Configuring anonymous redirect module";
  drush -y config-set anonymous_redirect.settings enable_redirect true

  echo "Configuring Devel module";
  drush -y config-set devel.settings devel_dumper kint
}

function import() {
  drush features:import -y schemadotorg
  drush features:import -y schemadotorg_descriptions
  drush features:import -y schemadotorg_demo
  # drush features:import -y schemadotorg_flexfield
  drush features:import -y schemadotorg_inline_entity_form
  drush features:import -y schemadotorg_jsonapi
  drush features:import -y schemadotorg_jsonld
  drush features:import -y schemadotorg_jsonld_endpoint
  drush features:import -y schemadotorg_paragraphs
  drush features:import -y schemadotorg_report
  drush features:import -y schemadotorg_taxonomy
}

function export() {
  drush features:export -y schemadotorg
  drush features:export -y schemadotorg_descriptions
  drush features:export -y schemadotorg_demo
  # drush features:export -y schemadotorg_flexfield
  drush features:export -y schemadotorg_inline_entity_form
  drush features:export -y schemadotorg_jsonapi
  drush features:export -y schemadotorg_jsonld
  drush features:export -y schemadotorg_jsonld_endpoint
  drush features:export -y schemadotorg_paragraphs
  drush features:export -y schemadotorg_report
  drush features:export -y schemadotorg_taxonomy
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
