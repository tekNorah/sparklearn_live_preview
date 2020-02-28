/**
 * @file
 * Expands the behaviour of the SparkLearn Live Preview.
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.SparkLearnLivePreview = {
    attach: function (context, settings) {
      function setLinkTargetNew() {
        $('.c-field--name-field-paragraph-body a', context).attr('target', '_blank');
        $('.c-field--name-field-learning-content a', context).attr('target', '_blank');
        $('.c-field--name-field-tags a', context).attr('target', '_blank');
      }

      // Call set target on page load.
      setLinkTargetNew();

      // Set target of links in Preview new tab/window.
      $.fn.set_target_new = function () {
        setLinkTargetNew();
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
