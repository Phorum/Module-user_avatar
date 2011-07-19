<?php

if (!defined("PHORUM")) return;

require_once PHORUM_PATH.'/mods/user_avatar/defaults.php';
require_once PHORUM_PATH.'/mods/user_avatar/api.php';

function mod_user_avatar_css_register($data)
{
    if ($data['css'] != 'css') return $data;

    $data['register'][] = array(
        "module" => "user_avatar",
        "where"  => "after",
        "source" => "template(user_avatar::css)"
    );
    return $data;
}

// Handle module installation.
function mod_user_avatar_common()
{
    global $PHORUM;

    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (empty($PHORUM["mod_user_avatar"]["mod_user_avatar_installed"])) {
        include("./mods/user_avatar/install.php");
    }

    // Setup template variables.
    $PHORUM["DATA"]["MOD_USER_AVATAR"]["WIDTH"] =
      $PHORUM["mod_user_avatar"]["max_width"];
    $PHORUM["DATA"]["MOD_USER_AVATAR"]["HEIGHT"] =
      $PHORUM["mod_user_avatar"]["max_height"];
    $PHORUM["DATA"]["URL"]["CC_AVATAR"] =
        phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=avatar");
}

// Add avatar images for the posting editor.
function mod_user_avatar_posting_custom_action($message)
{
    $messages = mod_user_avatar_apply_avatar_to_messages(array(1 => $message));
    return $messages[1];
}

// Add avatar images to messages on the read page.
function mod_user_avatar_read($messages)
{
    return mod_user_avatar_apply_avatar_to_messages($messages);
}

// Add avatar images to messages on the list page.
function mod_user_avatar_list($messages)
{
    return mod_user_avatar_apply_avatar_to_messages($messages);
}

// Add avatar images to user profiles.
function mod_user_avatar_profile($profile)
{
    return mod_user_avatar_apply_avatar_to_user($profile);
}

// Add avatar images to the active Phorum user.
function mod_user_avatar_common_post_user()
{
    global $PHORUM;
    $PHORUM['user'] = mod_user_avatar_apply_avatar_to_user($PHORUM['user']);
}

// Add an extra avatar option to the control center menu.
function mod_user_avatar_tpl_cc_menu_options_hook()
{
    global $PHORUM;

    $can_create  = mod_user_avatar_current_user_has_permission('create');
    $can_disable = mod_user_avatar_current_user_has_permission('disable');

    // Check if the user has permission to use avatars.
    if (!$can_create)
    {
        // We reset the user's avatar if he currently has one enabled.
        // This is not the best garbage collection method, but it's
        // the friendliest one. Stale configured avatars could come up
        // when changing avatar permissions and that is something that
        // after installation of the module normally is not done.
        if (!empty($PHORUM['user']['mod_user_avatar']['avatar']))
        {
            $PHORUM['user']['mod_user_avatar']['avatar'] = NULL;
            phorum_api_user_save(array(
                "user_id"         => $PHORUM["user"]["user_id"],
                "mod_user_avatar" => $PHORUM["user"]["mod_user_avatar"]
            ));
        }
    }

    // No permissions at all? Then do not display the control center menu item.
    if (!$can_create && !$can_disable) return;

    // Generate the require template data for the control panel menu button.
    if ($PHORUM["DATA"]["PROFILE"]["PANEL"] == 'avatar')
        $PHORUM["DATA"]["AVATAR_PANEL_ACTIVE"] = TRUE;

    // Show the menu button.
    include(phorum_get_template('user_avatar::cc_menu_item'));
}

// Add an extra avatar panel to the control center.
function mod_user_avatar_cc_panel($data)
{
    global $PHORUM;

    $can_create  = mod_user_avatar_current_user_has_permission('create');
    $can_disable = mod_user_avatar_current_user_has_permission('disable');

    if ($data['panel'] == 'avatar')
    {
        // Check if the user has permission to use avatar control center
        // panel. If not, then we simply display the profile summary page.
        if (!$can_create && !$can_disable)
        {
            $data['template'] = 'summary';
            $PHORUM['DATA']['PROFILE']['PANEL'] = 'summary';
        }
        else
        {
            // Make the permisisons available in the template data.
            $PHORUM['DATA']['PERMISSION']['AVATAR_CREATE']  = $can_create;
            $PHORUM['DATA']['PERMISSION']['AVATAR_DISABLE'] = $can_disable;

            // Separate include file, because of its length.
            include PHORUM_PATH.'/mods/user_avatar/cc_panel.php';

            $data['handled'] = TRUE;
        }
    }

    return $data;
}

// Cleanup avatars for users that are deleted.
function mod_user_avatar_user_delete($user_id)
{
    // Retrieve the list of avatar files for the user.
    require_once('./include/api/file.php');
    $files = phorum_api_file_list('avatar', $user_id, NULL);

    // Delete the files.
    foreach ($files as $file) {
        phorum_api_file_delete($file);
    }

    return $user_id;
}

// $type = "create" or "disable"
function mod_user_avatar_current_user_has_permission($type)
{
    $perm = $GLOBALS['PHORUM']['mod_user_avatar']['permission_'.$type];

    $permission_granted = TRUE;

    switch ($perm)
    {
      case AVATAR_PERM_NOBODY:
          $permission_granted = FALSE;
          break;

      case AVATAR_PERM_ADMIN:
          if (empty($GLOBALS['PHORUM']['user']['admin'])) {
              $permission_granted = FALSE;
          }
          break;

      case AVATAR_PERM_MODERATOR:
          if (empty($GLOBALS['PHORUM']['user']['admin']) &&
              empty($GLOBALS['PHORUM']['DATA']['MESSAGE_MODERATOR'])) {
              $permission_granted = FALSE;
          }
          break;

      default:
          // No further operation required. Permission is granted.
          break;
    }

    return $permission_granted;
}

// Add stale avatar temp-files to the list of stale files for the
// admin "purge files" feature.
function mod_user_avatar_file_purge_stale($stale_files)
{
    $avatar_tempfiles = phorum_api_file_list('avatar_tmp', NULL, NULL);
    $time_border = time() - PHORUM_MAX_EDIT_TIME;
    foreach ($avatar_tempfiles as $file) {
        if ($file['add_datetime'] < $time_border) {
            $stale_files[$file['file_id']] = $file;
        }
    }

    return $stale_files;
}

?>
