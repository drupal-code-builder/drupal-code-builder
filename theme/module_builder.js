
(function ($) {

/**
 * Enable or disable hooks defined by presets.
 */
Drupal.behaviors.moduleBuilderHookPresets = {
  attach: function (context, settings) {
    // Attach a click handler to each preset checkbox.
    $('#edit-hook-presets input').click(function () {
      group_checkbox = this;
      // Extract the group name from the id, and form the class that
      // member hook checkboxes have.
      group_name = group_checkbox.id.substr(5);
      $('.preset-' + group_name).each(function(index) {
        this.checked = group_checkbox.checked;
      });
    });
  }
};  

/**
 * Clears the default texts on click.
 * Only happens on a fresh form (ie not when the user clicks our back button).
 */
Drupal.behaviors.moduleBuilderClearDefaults = {
  attach: function (context, settings) {
    $('.fresh .form-text.required').click(function () {
      $(this).attr('value', '');
      $(this).unbind('click');
    });
    // If the user gets here with a tab and types, lose the click clearing.
    $('.fresh .form-text.required').keypress(function () {
      $(this).unbind('click');
    });
  }
};

})(jQuery);
