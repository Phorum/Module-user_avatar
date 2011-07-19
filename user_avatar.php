<?php

if (!defined("PHORUM")) return;

require_once('./mods/user_avatar/defaults.php');

// Handle module installation.
function mod_user_avatar_common()
{
    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (empty($GLOBALS['PHORUM']["mod_user_avatar"]["mod_user_avatar_installed"])) {
        include("./mods/user_avatar/install.php");
    }
}

// Add avatar images to messages that are being read.
function mod_user_avatar_read($messages)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // If the user doesn't want to show avatars, we are done.
    if (mod_user_avatar_current_user_has_permission('disable') &&
        !empty($PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"])) {
        return $messages;
    }

    // If the permission settings are set to only display moderator avatars
    // if a user is moderator for the active forum, then retrieve a list
    // of moderators.
    $moderators = NULL;
    if ($PHORUM['mod_user_avatar']['permission_create'] == AVATAR_PERM_MODERATOR &&
        !empty($PHORUM['mod_user_avatar']['moderator_only_in_mod_forums'])) {
        $moderators = phorum_api_user_list_moderators();
    }

    $file_url_template = phorum_get_url(PHORUM_FILE_URL, "file=%file_id%");

    $cache = array();

    foreach ($messages as $messageid => $message)
    {
        // Only registered users can have an avatar.
        if (empty($message["user_id"])) continue;

        // Use the cached avatar URL if we have one.
        if (isset($cache[$message["user_id"]])) {
            if ($cache[$message["user_id"]]) {
                $data = $cache[$message["user_id"]];
                // mod_user_avatar = backward compatibility
                $messages[$messageid]["mod_user_avatar"]   =
                    $messages[$messageid]["user_avatar"]   = $data[0];
                $messages[$messageid]["mod_user_avatar_w"] =
                    $messages[$messageid]["user_avatar_w"] = $data[1];
                $messages[$messageid]["mod_user_avatar_h"] =
                    $messages[$messageid]["user_avatar_h"] = $data[2];
            }
            continue;
        }

        // Handle special permission, where the avatar is only shown
        // for moderators that actually have moderator permission for
        // the current forum.
        if ($moderators!==NULL && !isset($moderators[$message['user_id']])){
            continue;
        }

        // Retrieve the author information.
        if (isset($message['user'])) {
             $author = $message['user'];
        } else {
             $author = phorum_api_user_get($message["user_id"]);
        }

        // In case only admins can have an avatar, check if the user
        // is an admin or not.
        if ($PHORUM['mod_user_avatar']['permission_create'] == AVATAR_PERM_ADMIN) {
            if (empty($author['admin'])) continue;
        }

        // If the author has no avatar enabled, we're done.
        if (empty($author["mod_user_avatar"]["avatar"]) ||
            $author["mod_user_avatar"]["avatar"] == -1) {
            $cache[$message["user_id"]] = 0; // negative caching.
            continue;
        }

        // This user has an avatar. Add it to the message data.
        $file_id = $author["mod_user_avatar"]["avatar"];
        $url = str_replace('%file_id%', $file_id, $file_url_template);
        // mod_user_avatar = backward compatibilty.
        $messages[$messageid]["mod_user_avatar"] =
            $messages[$messageid]["user_avatar"] = $url;

        // If we have width + height info available for the avatar
        // image, then add it to the message data.
        $w = 0; $h = 0;
        if (!empty($author["mod_user_avatar"]["image_info"][$file_id])) {
            $info = $author["mod_user_avatar"]["image_info"][$file_id];
            $w = $info['width'];
            $h = $info['height'];
            // mod_user_avatar = backward compatibilty.
            $messages[$messageid]["mod_user_avatar_w"] =
                $messages[$messageid]["user_avatar_w"] = $w;
            $messages[$messageid]["mod_user_avatar_h"] =
                $messages[$messageid]["user_avatar_h"] = $h;
        }

        // Cache the info, in case we encounter this user more
        // often in the loop.
        $cache[$message["user_id"]][0] = $url; // positive caching.
        $cache[$message["user_id"]][1] = $w;   // avatar image width.
        $cache[$message["user_id"]][2] = $h;   // avatar image height.
    }

    unset($cache);

    return $messages;
}

// Add avatar images to user profiles.
function mod_user_avatar_profile($profile, $from_post_user = FALSE)
{
    // Check if we have an avatar for this user.
    if (empty($profile["mod_user_avatar"]["avatar"]) ||
        $profile["mod_user_avatar"]["avatar"] == -1)
    {
        // We should not unset mod_user_avatar when we're called from
        // the common_post_user hook, because that would break the
        // $PHORUM['user']['mod_user_avatar'] data that is required for
        // the control panel to work.
        if (!$from_post_user) unset($profile["mod_user_avatar"]);

        return $profile;
    }

    // Add the avatar to the template data.
    $file_id = $profile['mod_user_avatar']["avatar"];
    $profile["user_avatar"] = phorum_get_url(
        PHORUM_FILE_URL,
        "file=$file_id"
    );
    // mod_user_avatar = backward compatibility
    if (!$from_post_user) {
        $profile['mod_user_avatar'] = $profile['user_avatar'];
    }

    // Add the avatar image size to the template data, if available.
    if (!empty($profile['mod_user_avatar']["image_info"][$file_id])) {
        $info = $profile['mod_user_avatar']['image_info'][$file_id];
        $profile["user_avatar_w"] = $info['width'];
        $profile["user_avatar_h"] = $info['height'];
        // mod_user_avatar = backward compatibility
        if (!$from_post_user) {
            $profile['mod_user_avatar_w'] = $profile['user_avatar_w'];
            $profile['mod_user_avatar_h'] = $profile['user_avatar_h'];
        }
    }

    return $profile;
}

// Add avatar images to the active Phorum user.
function mod_user_avatar_common_post_user()
{
    global $PHORUM;
    $PHORUM['user'] = mod_user_avatar_profile($PHORUM['user'], TRUE);
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
    $PHORUM["DATA"]["URL"]["CC_AVATAR"] =
        phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=avatar");

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
            include('./mods/user_avatar/cc_panel.php');

            $data['handled'] = TRUE;
        }
    }

    return $data;
}

// Cleanup avatars for users that are deleted.
function mod_user_avatar_user_delete($user_id)
{
    // Retrieve the list of avatar files for the user.
    require_once('./include/api/file_storage.php');
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

?>
