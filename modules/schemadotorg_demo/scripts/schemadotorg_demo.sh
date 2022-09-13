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
  echo;
  echo "The below commands should be executed from the root of your Drupal installation."
  echo;
  echo "Usage:"
  echo;
  echo "./web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh help";
  echo "./web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh install";
  echo "./web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh demo";
  echo "./web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh translate";
  echo "./web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh next";
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

  drush -y pm:enable schemadotorg_demo_standard;
}

function demo() {
  install
  drush -y pm:enable schemadotorg_demo;
}

function translate() {
  drush -y pm:enable schemadotorg_demo_standard_translation;
  drush locale:check
  drush locale:update
  drush -y pm:enable schemadotorg_demo_umami_content;
}

function next() {
  drush -y pm:enable schemadotorg_demo_next;
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
