/**
 * @file
 * Schema.org demo standard behaviors.
 */

(function (drupalSettings) {

  'use strict';

  // Clear local storage to reset the state of all details widget for demos.
  // @see schemadotorg/js/schemadotorg.details.js
  localStorage.clear();

  // Redirect anonymous users to /user/login.
  // Using JavaScript so that Acquia's Cloud IDE's 'share' query parameter
  // generates the 'share' cookie as expected.
  if (drupalSettings.user.uid === 0
    && drupalSettings.path.currentPath !== 'user/login') {
    window.location = drupalSettings.path.baseUrl + 'user/login?destination=' + drupalSettings.path.baseUrl;
  }

  if (drupalSettings.path.currentPath === 'user/login') {
    window.addEventListener('load', (event) => {
      // Display welcome message.
      const messages = new Drupal.Message();
      messages.add(Drupal.t('Please log in to the Schema.org Blueprints Demo website.'), {type: 'status'});

      // Set user name and password to demo/demo.
      setTimeout(() => {
        document.getElementById('edit-name').value = 'demo';
        document.getElementById('edit-pass').value = 'demo';
      }, 100);
    });
  }

} (drupalSettings));
