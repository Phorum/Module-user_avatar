<?php
// ======================================================================
// This control center script handles deleting avatars and updating
// the Avatar settings for the active Phorum user.
// After the updates, the cc_panel/index.php script will be loaded
// to show the updated avatar list.
// ======================================================================

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once PHORUM_PATH.'/include/api/file.php';

// Retrieve the active list of avatar files for the user.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

// Keep track if we need to store the user data.
$do_store_user_data = FALSE;

// ----------------------------------------------------------------------
// Apply the configuration settings
// ----------------------------------------------------------------------

// Update the active avatar. Clean up the active avatar if the user has
// no permission to create avatars. Shouldn't really be neccessary, but
// it's a way of cleaning up avatars after changing user create permission.
if ($PHORUM['DATA']['PERMISSION']['AVATAR_CREATE']) {
    if (isset($_POST['avatar'])) {
        $PHORUM["user"]["mod_user_avatar"]["avatar"] = (int) $_POST["avatar"];
    }
} else {
    // Might be set on rare occasion, where a user is in the avatar panel,
    // while the admin disables the user's right to use avatars.
    unset($_POST['avatar']);
    $PHORUM["user"]["mod_user_avatar"]["avatar"] = -1;
}

// Delete avatars that are selected for deletion.
if (!empty($_POST['delete'])) {
    foreach($_POST["delete"] as $file_id)
    {
        if (phorum_api_file_check_delete_access($file_id)) {
            phorum_api_file_delete($file_id);
            unset($avatars[$file_id]);
        }
    }
}

// Update the config option to disable displaying of avatars.
if ($PHORUM['DATA']['PERMISSION']['AVATAR_DISABLE']) {
    $PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"] =
        empty($_POST["disable_avatar_display"]) ? FALSE : TRUE;
} else {
    $PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"] = FALSE;
}

$do_store_user_data = TRUE;

$data['okmsg'] = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];

// ----------------------------------------------------------------------
// Cleaning up for consistency.
// ----------------------------------------------------------------------

// Unset the active avatar if the avatar file doesn't exist anymore.
if (!empty($PHORUM["user"]["mod_user_avatar"]["avatar"]) &&
    $PHORUM["user"]["mod_user_avatar"]["avatar"] > 0 &&
    !isset($avatars[$PHORUM["user"]["mod_user_avatar"]["avatar"]])) {
    $PHORUM["user"]["mod_user_avatar"]["avatar"] = -1;
    $do_store_user_data = TRUE;
}

// ----------------------------------------------------------------------
// Store the user data if required.
// ----------------------------------------------------------------------

if ($do_store_user_data) {
    phorum_api_user_save(array(
        "user_id"         => $PHORUM["user"]["user_id"],
        "mod_user_avatar" => $PHORUM["user"]["mod_user_avatar"]
    ));
}

// ----------------------------------------------------------------------
// Show the avatar list
// ----------------------------------------------------------------------

include dirname(__FILE__) . '/index.php';

?>
