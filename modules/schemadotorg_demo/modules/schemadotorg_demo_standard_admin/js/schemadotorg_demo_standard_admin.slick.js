/* eslint-disable strict */

/**
 * @file
 * Schema.org demo standard slick behaviors.
 */

"use strict";

((Drupal, once) => {

  /**
   * Enhances paragraph components with a basic Slick carousel.
   *
   * @type {Drupal~behavior}
   *
   * @see schemadotorg_demo_standard_admin_entity_view_alter()
   */
  Drupal.behaviors.schemaDotOrgDemoSlick = {
    attach: function attach(context) {
      once('schemadotorg-demo-slick', '.schemadotorg-demo-slick', context)
        .forEach((element) => {
          $(element).slick({
            slidesToShow: 1,
            dots: true,
            centerMode: true,
            autoplay: true,
            speed: 300,
          });
        });
    }
  };

})(Drupal, once);
