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
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh setup";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh generate";
  echo "./web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh teardown";
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
  composer require drupal/chosen
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
  echo "Installing Schema.org demo with core and contrib modules";
  drush -y pm-enable schemadotorg_demo;

  echo "Installing contrib modules";
  drush -y pm-enable \
    admin_toolbar\
    admin_toolbar_tools\
    anonymous_redirect\
    chosen\
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

  echo "Configuring Chosen module";
  drush -y config-set chosen.settings minimum_single 0
  drush -y config-set chosen.settings minimum_multiple 0
}

function import() {
  drush features:import -y schemadotorg
  drush features:import -y schemadotorg_descriptions
  drush features:import -y schemadotorg_flexfield
  drush features:import -y schemadotorg_jsonapi
  drush features:import -y schemadotorg_jsonld
  drush features:import -y schemadotorg_jsonld_endpoint
  drush features:import -y schemadotorg_report
  drush features:import -y schemadotorg_taxonomy
}

################################################################################
# Basic Demo
################################################################################

function setup() {
  drush schemadotorg:create-type -y media:AudioObject media:DataDownload media:ImageObject media:VideoObject
  drush schemadotorg:create-type -y taxonomy_term:DefinedTerm

  drush schemadotorg:create-type -y paragraph:ContactPoint
  drush schemadotorg:create-type -y node:Place node:Organization node:Person node:Event
  drush schemadotorg:create-type -y node:Article node:WebPage
}

function teardown() {
  drush devel-generate:media --kill 0
  drush devel-generate:content --kill 0

  drush schemadotorg:delete-type -y --delete-fields node:Article node:WebPage
  drush schemadotorg:delete-type -y --delete-entity node:Place node:Organization node:Person node:Event
  drush schemadotorg:delete-type -y --delete-entity paragraph:ContactPoint

  drush schemadotorg:delete-type -y --delete-fields taxonomy_term:DefinedTerm
  drush schemadotorg:delete-type -y --delete-fields media:AudioObject media:DataDownload media:ImageObject media:VideoObject
}

function generate() {
  drush devel-generate:users --kill
  drush devel-generate:media --kill
  drush devel-generate:terms --kill --bundles=tags
  drush devel-generate:content --kill --add-type-label --skip-fields=menu_link\
    --bundles=article,page,person,organization,place,event
}

################################################################################
# Block.
################################################################################

function setup_block() {
  drush schemadotorg:create-type -y media:ImageObject
  drush schemadotorg:create-type -y block_content:Quotation block_content:WebContent block_content:ItemList
}

function teardown_block() {
  drush schemadotorg:delete-type -y block_content:Quotation block_content:WebContent block_content:ItemList
}


################################################################################
# Collection.
################################################################################

function setup_collection() {
  drush schemadotorg:create-type -y media:ImageObject
  drush schemadotorg:create-type -y node:MediaGallery node:ImageGallery node:VideoGallery
}

function teardown_collection() {
  drush devel-generate:content --kill --bundles=media_gallery,image_gallery,video_gallery 0
  drush schemadotorg:delete-type -y node:MediaGallery node:ImageGallery node:VideoGallery
}

function generate_collection() {
  drush devel-generate:content --kill --add-type-label --skip-fields=menu_link\
    --bundles=media_gallery,image_gallery,video_gallery 15
}

################################################################################
# HowTo.
################################################################################

function setup_howto() {
  drush schemadotorg:create-type -y media:ImageObject
  drush schemadotorg:create-type -y paragraph:HowToSupply paragraph:HowToTool paragraph:HowToDirection paragraph:HowToTip paragraph:HowToStep paragraph:HowToSection
  drush schemadotorg:create-type -y node:HowTo
}

function teardown_howto() {
  drush devel-generate:content --kill --bundles=how_to 0
  drush schemadotorg:delete-type -y paragraph:HowToSupply paragraph:HowToTool paragraph:HowToDirection paragraph:HowToTip paragraph:HowToStep paragraph:HowToSection
  drush schemadotorg:delete-type -y node:HowTo
}

function generate_howto() {
  drush devel-generate:content --kill --add-type-label --skip-fields=menu_link\
    --bundles=how_to 5
}

################################################################################
# Food.
################################################################################

function setup_food() {
  drush schemadotorg:create-type -y media:ImageObject
  drush schemadotorg:create-type -y paragraph:NutritionInformation
  drush schemadotorg:create-type -y paragraph:MenuItem paragraph:MenuSection
  drush schemadotorg:create-type -y node:Menu node:Recipe node:FoodEstablishment
}

function teardown_food() {
  drush devel-generate:content --kill --bundles=recipe,menu,food_establishment
  drush schemadotorg:delete-type -y --delete-entity paragraph:NutritionInformation
  drush schemadotorg:delete-type -y --delete-entity paragraph:MenuSection paragraph:MenuItem
  drush schemadotorg:delete-type -y --delete-entity node:Menu node:Recipe node:FoodEstablishment
}

function generate_food() {
  drush devel-generate:content --kill --add-type-label --skip-fields=menu_link\
    --bundles=recipe,menu,food_establishment
}

################################################################################
# Entertainment.
################################################################################

function setup_entertainment() {
  drush schemadotorg:create-type -y media:ImageObject
  drush schemadotorg:create-type -y node:Movie
  drush schemadotorg:create-type -y node:TVEpisode node:TVSeason node:TVSeries
  drush schemadotorg:create-type -y node:PodcastEpisode node:PodcastSeason node:PodcastSeries
}

function teardown_entertainment() {
  drush devel-generate:content --kill --bundles=movie,tv_series,tv_season,tv_episode,podcast_series,podcast_season,podcast_episode
  drush schemadotorg:delete-type -y --delete-entity node:Movie
  drush schemadotorg:delete-type -y --delete-entity node:TVEpisode node:TVSeason node:TVSeries
  drush schemadotorg:delete-type -y --delete-entity node:PodcastEpisode node:PodcastSeason node:PodcastSeries
}

function generate_entertainment() {
  drush devel-generate:content --kill --add-type-label --skip-fields=menu_link\
    --bundles=movie,tv_series,tv_season,tv_episode,podcast_series,podcast_season,podcast_episode
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
