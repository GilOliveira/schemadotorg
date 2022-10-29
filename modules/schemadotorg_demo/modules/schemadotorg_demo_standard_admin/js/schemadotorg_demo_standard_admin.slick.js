/* eslint-disable strict */

/**
 * @file
 * Schema.org demo standard slick behaviors.
 */

"use strict";

((Drupal, once) => {

  /**
   * Enhances MediaGallery with Slick carousel.
   *
   * @type {Drupal~behavior}
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
