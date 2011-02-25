
/**
 * For a given hook grouping, selects/deselects all hooks associated with it.
 *
 * The bulk of this function (except the crappy hard-coded stuff ;)) was
 * courtesy of David Carrington (Thox), with enhancements
 * made by Steven Wittens (Unconed). Thanks a lot, guys!! :D
 */
function check_hooks(grouping, hooks) {
  // Loop through the hooks
  for (i = 0; hook = hooks[i]; i++) {
    // Find the relevant checkbox
    hook_groups = Array('authentication', 'core', 'node');
    for (j = 0; group = hook_groups[j]; j++) {
      id = 'edit-hooks-' + group + '-' + hook.replace(/_/g, '-');
      if (document.getElementById(id)) {
        checkbox = document.getElementById(id);
        break;
      }
    }

    // Set the checkbox status to the status of the clicked one
    if (typeof checkbox.checkCount == 'undefined') {
      checkbox.checkCount = 0;
    }
    checkbox.checkCount += grouping.checked ? 1 : -1;
    checkbox.checked = checkbox.checkCount > 0;
  }
}

/**
 * Clears the default texts on click.
 * Only happens on a fresh form (ie not when the user clicks our back button).
 */
$(document).ready(function() {
  $('.fresh .form-text.required').click(function () {
    $(this).attr('value', '');
    $(this).unbind('click');
  });
  // If the user gets here with a tab and types, lose the click clearing.
  $('.fresh .form-text.required').keypress(function () {
    $(this).unbind('click'); 
  });  
});
  

