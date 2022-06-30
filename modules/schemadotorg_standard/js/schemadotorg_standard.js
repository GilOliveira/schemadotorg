/**
 * @file
 * Schema.org standard behaviors.
 */

(function (drupalSettings) {

  'use strict';

  // Redirect anonymous users to /user/login.
  // Using JavaScript so that Acquia's Cloud IDE's 'share' query parameter
  // generates the 'share' cookie as expected.
  if (drupalSettings.user.uid === 0
    && drupalSettings.path.currentPath !== 'user/login') {
    window.location = drupalSettings.path.baseUrl + 'user/login?destination=' + drupalSettings.path.baseUrl;
  }

  window.addEventListener('load', (event) => {
    if (drupalSettings.path.currentPath === 'user/login') {
      setTimeout(() => {
        document.getElementById('edit-name').value = 'demo';
        document.getElementById('edit-pass').value = 'demo';
      }, 100);
    }
  });

} (drupalSettings));
