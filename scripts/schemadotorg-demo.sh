#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg-demo.sh";
  echo;
  echo "Download, installs, and configures a demo of the Schema.org Blueprints module."
  echo;
  echo "This scripts assumes you are starting with a plain vanilla instance of Drupal."
  echo;
  echo "The below commands should be executed from the root of your Drupal installation."
  echo;
  echo "Usage:"
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh help";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh require";
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
  composer require drupal/computed_field
  composer require drupal/devel
  composer require drupal/features
  composer require drupal/field_group
  composer require drupal/field_token_value
  composer require drupal/flexfield
  composer require drupal/gin
  composer require drupal/gin_login
  composer require drupal/inline_entity_form
  composer require drupal/jsonapi_extras
  composer require drupal/key_value_field
  composer require drupal/paragraphs
  composer require drupal/smart_date
  composer require drupal/time_field
  composer require drupal/token
}

function install() {
  echo "Installing core modules";
  drush -y pm-enable \
    inline_form_errors\
    media\
    media_library\
    settings_tray;

  echo "Installing contrib modules";
  drush -y pm-enable \
    admin_toolbar\
    admin_toolbar_tools\
    anonymous_redirect\
    coffee\
    devel\
    devel_generate\
    features\
    jsonapi_extras\
    paragraphs\
    webprofiler;

  echo "Installing Schema.org modules";
  drush -y pm-enable schemadotorg\
    schemadotorg_descriptions\
    schemadotorg_export\
    schemadotorg_report\
    schemadotorg_ui\
    schemadotorg_jsonapi\
    schemadotorg_jsonld;

  echo "Installing field related modules";
  drush -y pm-enable \
    address\
    field_group\
    field_token_value\
    telephone\
    time_field\
    flexfield\
    key_value_field;
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

  echo "Configuring JSON:API extras module";
  drush -y config-set jsonapi_extras.settings path_prefix api
  drush -y config-set jsonapi_extras.settings include_count true
  drush -y config-set jsonapi_extras.settings default_disabled true

  echo "Configuring Devel module";
  drush -y config-set devel.settings devel_dumper kint
}

function_name=$1; shift;

if function_exists $function_name; then
  $function_name $@;
else
  echo "Function to '$function_name' does not exist.";
  echo;
  help;
fi
