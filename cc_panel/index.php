<?php
// ======================================================================
// This control center script shows the current list of avatars
// and the avatar configuration for the current Phorum user.
// ======================================================================

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once PHORUM_PATH.'/include/api/file.php';
require_once PHORUM_PATH.'/include/api/system.php';
require_once PHORUM_PATH.'/include/api/format.php';
require_once PHORUM_PATH.'/include/api/http_get.php';

// Setup dummy avatar data if the user has not configured the avatar yet. 
if (empty($PHORUM["user"]["mod_user_avatar"])) {
    $PHORUM["user"]["mod_user_avatar"] = array();
}
if (!isset($PHORUM["user"]["mod_user_avatar"]["avatar"])) {
    $PHORUM["user"]["mod_user_avatar"]["avatar"] = -1;
}

// Retrieve the list of locally stored avatars for the user.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

// Format the avatar data.
$found_active = FALSE;
foreach ($avatars as $id => $file)
{
    $avatars[$id]["url"] = phorum_get_url(
        PHORUM_FILE_URL,
        "file=$id",
        "filename=".urlencode($file['filename'])
    );

    // Mark the currently active avatar as selected.
    if (isset($PHORUM["user"]["mod_user_avatar"]["avatar"]) &&
        $file['file_id'] == $PHORUM["user"]["mod_user_avatar"]["avatar"]) {
        $avatars[$id]["selected"] = TRUE;
        $found_active = TRUE;
    } else {
        $avatars[$id]["selected"] = FALSE;
    }

    $avatars[$id]["option_avatar"] = TRUE;
    $avatars[$id]["text"] = $lang['SelectAvatar'];
}

// Reverse the avatar order, so new avatars will show up at the top.
$avatars = array_reverse($avatars);

// Add the Gravatar option to the list.
if (!empty($PHORUM['mod_user_avatar']['gravatar_enabled']))
{
    $gravatar_url = mod_user_avatar_get_gravatar_url($PHORUM['user']);
    $selected = $PHORUM["user"]["mod_user_avatar"]["avatar"] == -2;
    if ($selected) $found_active = TRUE;
    $avatars[-2] = array(
        'file_id'         => -2,
        'selected'        => $selected,
        'url'             => $gravatar_url,
        'filename'        => 'Gravatar',
        'option_gravatar' => TRUE,
        'text'            => str_replace(
            '%email%',
            htmlspecialchars($PHORUM['user']['email']),
            $lang['SelectGravatar']
        )
    );
}

// Add the "No avatar" option to the list.
// If a default avatar image is configured in the settings, then
// use that image for this option. Otherwise, fallback to the
// built-in anonymous avatar image.
$anonymous_url =
    $PHORUM['mod_user_avatar']['default_avatar']
    ? $PHORUM['mod_user_avatar']['default_avatar']
    : $PHORUM['http_path'] . '/mods/user_avatar/images/anonymous-avatar.gif';
$selected = $PHORUM["user"]["mod_user_avatar"]["avatar"] == -1;
if ($selected) $found_active = TRUE;
$avatars[-1] = array(
    'file_id'         => -1,
    'selected'        => $selected,
    'url'             => $anonymous_url,
    'filename'        => 'None',
    'option_none'     => TRUE,
    'text'            => $lang['SelectNone']
);

// If no active avatar was found, then select the first one by default.
if (!$found_active)
{
    reset($avatars);
    list($id, $avatar) = each($avatars);
    reset($avatars);

    $avatars[$id]['selected'] = TRUE;
    $PHORUM['user']['mod_user_avatar']['avatar'] = $avatars[$id]['file_id'];

    phorum_api_user_save(array(
        "user_id"         => $PHORUM["user"]["user_id"],
        "mod_user_avatar" => $PHORUM["user"]["mod_user_avatar"]
    ));
}

// ----------------------------------------------------------------------
// Setup template data.
// ----------------------------------------------------------------------

$PHORUM["DATA"]["FILES"] = $avatars;

$PHORUM["DATA"]["NUMBER_OF_FILES"] = count($avatars) - 1; // -1 for "none"

$PHORUM["DATA"]["mod_user_avatar"]["disable_avatar_display"] =
    !empty($PHORUM['user']["mod_user_avatar"]["disable_avatar_display"]);

$max_upload = phorum_api_system_get_max_upload();
$PHORUM["DATA"]["MAX_UPLOAD_SIZE"] = phorum_api_format_filesize($max_upload[1]);

$PHORUM['DATA']['UPLOAD_ENABLED'] = 
    !empty($PHORUM["mod_user_avatar"]["upload_enabled"]);
$PHORUM['DATA']['URL_ENABLED'] = 
    !empty($PHORUM["mod_user_avatar"]["url_enabled"]);
$PHORUM['DATA']['GRAVATAR_ENABLED'] = 
    !empty($PHORUM["mod_user_avatar"]["gravatar_enabled"]);

$PHORUM["DATA"]["LANG"]["mod_user_avatar"]["AvatarLimit"] = str_replace(
    '%max_avatars%',
    $PHORUM["mod_user_avatar"]['max_avatars'],
    $lang["AvatarLimit"]
);

// Tell the Phorum control center script what template to load.
$data['template'] = 'user_avatar::cc_panel';

?>
