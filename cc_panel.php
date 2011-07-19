<?php

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once('./include/api/file_storage.php');

global $PHORUM;
$lang = $PHORUM["DATA"]["LANG"]["mod_user_avatar"];

$PHORUM["DATA"]["HEADING"] = $lang['AvatarHeading'];

// Retrieve the active list of avatars for the user.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

// Keep track if we need to store the user data.
$do_store_user_data = FALSE;

$data["error"] = NULL;

if (empty($PHORUM["user"]["mod_user_avatar"])) {
    $PHORUM["user"]["mod_user_avatar"] = array();
}
if (empty($PHORUM["user"]["mod_user_avatar"]["image_info"])) {
    $PHORUM["user"]["mod_user_avatar"]["image_info"] = array();
}

// ----------------------------------------------------------------------
// Handle storing a newly uploaded avatar.
// ----------------------------------------------------------------------

if (!empty($_FILES) && is_uploaded_file($_FILES["newfile"]["tmp_name"]))
{
    if ($PHORUM['DATA']['PERMISSION']['AVATAR_CREATE'])
    {
        $extension = "";
        $filename = $_FILES["newfile"]["name"];
        $dotpos = strrpos($filename, ".");
        if ($dotpos !== FALSE) {
            $extension = strtolower(substr($filename, $dotpos+1));
        }
        $width  = 0;
        $height = 0;

        $max_allowed = $PHORUM["mod_user_avatar"]['max_avatars'];

        // Check if the user already uploaded the maximum number
        // of avatars allowed.
        if(count($avatars) >= $max_allowed)
        {
            $data['error'] = str_replace(
                '%max_avatars%',
                $max_allowed,
                $lang["ErrorTooManyAvatars"]
            );
        }

        // Check if the file extension is allowed.
        if ($data["error"] === NULL &&
            !in_array($extension, $PHORUM["mod_user_avatar"]["file_types"])) {
            $data['error'] = $lang["ErrorNotInAllowedFileTypes"];
        }

        // Check if the file does not exceed the allowed dimensions.
        if ($data['error'] === NULL)
        {
            $imagedata = getimagesize($_FILES["newfile"]["tmp_name"]);

            // Check if it's really a supported image type, just to
            // make sure once again (in case the content of the image
            // mismatches the file extension).
            if (!$imagedata[2] || (
                 $imagedata[2] !== IMAGETYPE_JPEG &&
                 $imagedata[2] !== IMAGETYPE_PNG  &&
                 $imagedata[2] !== IMAGETYPE_GIF
                )) {
                $data['error'] = $lang["ErrorNotInAllowedFileTypes"];
            }
            else {
                $width  = $imagedata[0];
                $height = $imagedata[1];
                if ($width  > $PHORUM["mod_user_avatar"]["max_width"] ||
                    $height > $PHORUM["mod_user_avatar"]["max_height"]) {
                    $data['error'] = $lang["ErrorTooLargeDimensions"];
                }
            }
        }

        // Check if the maximum allowed file size is not exceeded.
        if ($data['error'] === NULL && $_FILES['newfile']['size']/1024 > $PHORUM["mod_user_avatar"]['max_filesize']) {
             $data['error'] = $lang["ErrorTooLargeFileSize"];
        }

        // The file is okay.
        if ($data['error'] === NULL)
        {
            // Read in the uploaded file.
            $fp = fopen($_FILES["newfile"]["tmp_name"], "r");
            $file_data = fread($fp, $_FILES["newfile"]["size"]);
            fclose($fp);

            // Create the file array for the file storage API.
            $file = array(
                "user_id"   => $PHORUM["user"]["user_id"],
                "filename"  => $filename,
                "filesize"  => $_FILES["newfile"]["size"],
                "file_data" => $file_data,
                "link"      => "avatar"
            );

            // Store the file.
            if (!($file_ret = phorum_api_file_store($file))) {
                $data['error'] = phorum_api_strerror();
            }
            else
            {
                // Add the new file id to the working list of avatars for
                // the user, so we don't have to reload the full list from
                // the database here..
                $avatars[$file_ret['file_id']] = $file_ret;

                // Keep track of the image's size.
                $PHORUM["user"]["mod_user_avatar"]["image_info"][$file_ret['file_id']] = array(
                    'width'  => $width,
                    'height' => $height
                );
                $do_store_user_data = TRUE;

                $data['okmsg'] = $PHORUM["DATA"]["LANG"]["FileAdded"];
            }
        }
    }
}

// ----------------------------------------------------------------------
// Handle deleting avatars and updating settings.
// ----------------------------------------------------------------------

elseif (count($_POST))
{
    // Delete avatars.
    if (!empty($_POST['delete'])) {
        foreach($_POST["delete"] as $file_id) {
            if (phorum_api_file_check_delete_access($file_id)) {
                phorum_api_file_delete($file_id);
                unset($avatars[$file_id]);
                unset($PHORUM["user"]["mod_user_avatar"]["image_info"][$file_id]);
            }
        }
    }

    // Update selected avatar. Clean up the active avatar if the user has
    // no permission to create avatars. Shouldn't really be neccessary, but
    // it's a way of cleaning up avatars after changing user create permission.
    if ($PHORUM['DATA']['PERMISSION']['AVATAR_CREATE']) {
        $PHORUM["user"]["mod_user_avatar"]["avatar"] = (int) $_POST["avatar"];
    } else {
        // Might be set on rare occasion, where a user is in the avatar panel,
        // while the admin disables the user's right to use avatars.
        unset($_POST['avatar']);

        $PHORUM["user"]["mod_user_avatar"]["avatar"] = -1;
    }

    // Update disabling of avatars.
    if ($PHORUM['DATA']['PERMISSION']['AVATAR_DISABLE']) {
        $PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"] =
            empty($_POST["disable_avatar_display"]) ? FALSE : TRUE;
    } else {
        $PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"] = FALSE;
    }

    $do_store_user_data = TRUE;

    $data['okmsg'] = $PHORUM["DATA"]["LANG"]["ChangesSaved"];
}

// ----------------------------------------------------------------------
// Some upgrading and cleaning up for consistency.
// ----------------------------------------------------------------------

// Convert the old avatar list. In older versions of this module, the
// avatar file were a subset of the personal files, where an avatar
// list was stored in the user's profile to remember which ones were
// avatars. This new version uses a more clean list of files, which
// have their own "avatar" link type.
if (isset($PHORUM["user"]["mod_user_avatar"]["users_avatars"]))
{
    if (is_array($PHORUM["user"]["mod_user_avatar"]["users_avatars"])) {
        foreach ($PHORUM["user"]["mod_user_avatar"]["users_avatars"] as $id) {
            $file = phorum_api_file_retrieve($id);
            if (!empty($personal_file) &&
                $file['user_id'] == $PHORUM['user']['user_id'] &&
                $file['link'] == PHORUM_LINK_USER) {

                $file['link'] = 'avatar';
                phorum_api_file_store($file);

                $avatars[$id] = $file;
            }
        }
    }

    unset($PHORUM["user"]["mod_user_avatar"]["users_avatars"]);

    $do_store_user_data = TRUE;
}

// Unset the active avatar if the avatar file doesn't exist anymore
// or upgrade an old avatar file if the avatar file is available as
// a personal user file (that was the way of storing avatars for the
// Phorum 5.1 version of the module).
if (!empty($PHORUM["user"]["mod_user_avatar"]["avatar"]) &&
    $PHORUM["user"]["mod_user_avatar"]["avatar"] != -1 &&
    !isset($avatars[$PHORUM["user"]["mod_user_avatar"]["avatar"]]))
{
    $file_id = $PHORUM["user"]["mod_user_avatar"]["avatar"];
    $personal_file = phorum_api_file_retrieve($file_id);

    // Reset the active avatar setting if the file does not exist at all.
    if (empty($personal_file) ||
        $personal_file['user_id'] != $PHORUM['user']['user_id'] ||
        $personal_file['link'] != PHORUM_LINK_USER)
    {
        $PHORUM["user"]["mod_user_avatar"]["avatar"] = -1;

        $do_store_user_data = TRUE;
    }
    // Convert the existing personal user file into an avatar file.
    else {
        $personal_file['link'] = 'avatar';
        phorum_api_file_store($personal_file);

        $avatars[$file_id] = $personal_file;
    }
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
// Determine the list of available avatars for the current user.
// ----------------------------------------------------------------------

$total_size=0;

// Retrieve a fresh list of avatars for the user. We did keep the
// $avatars array up-to-date in the above code, but some data like
// "add_datetime" is mising from them.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

$info = $PHORUM['user']['mod_user_avatar']['image_info'];

foreach ($avatars as $id => $file)
{
    $dimensions = NULL;
    if (isset($info[$id]['width']) && isset($info[$id]['height'])) {
        $dimensions = $info[$id]['width'] . ' x ' . $info[$id]['height'];
    }
    $avatars[$id]["dimensions"] = $dimensions;

    $avatars[$id]["filesize"] = phorum_filesize($file["filesize"]);
    $avatars[$id]["raw_dateadded"] = $file["add_datetime"];
    $avatars[$id]["dateadded"] = phorum_date($PHORUM["short_date_time"], $file["add_datetime"]);
    $avatars[$id]["url"] = phorum_get_url(PHORUM_FILE_URL, "file=$id", "filename=".urlencode($file['filename']));

    // Mark the currently active avatar as selected.
    if (isset($PHORUM["user"]["mod_user_avatar"]["avatar"])) {
        $avatars[$id]["selected"] = ($file['file_id'] == $PHORUM["user"]["mod_user_avatar"]["avatar"]);
    } else {
        $avatars[$id]["selected"] = FALSE;
    }

    $total_size += $file["filesize"];
}

// ----------------------------------------------------------------------
// Setup template data.
// ----------------------------------------------------------------------

$data['template'] = 'user_avatar::cc_panel';

$PHORUM["DATA"]["FILES"] = $avatars;
$PHORUM["DATA"]["NUMBER_OF_FILES"] = count($avatars);
$PHORUM["DATA"]["mod_user_avatar"]["disable_avatar_display"] =
    !empty($PHORUM['user']["mod_user_avatar"]["disable_avatar_display"]);

if ($PHORUM['mod_user_avatar']['max_filesize']) {
    $PHORUM["DATA"]["FILE_SIZE_LIMIT"] = str_replace(
        array(
            '%filesize%',
            '%width%',
            '%height%'
        ),
        array(
            phorum_filesize($PHORUM["mod_user_avatar"]['max_filesize']*1024),
            $PHORUM["mod_user_avatar"]["max_width"],
            $PHORUM["mod_user_avatar"]["max_height"]
        ),
        $lang["FileSizeLimits"]
    );
}

if ($PHORUM["mod_user_avatar"]["file_types"]) {
    $file_type_list = implode(", ",$PHORUM["mod_user_avatar"]["file_types"]);
    $PHORUM["DATA"]["FILE_TYPE_LIMIT"] = str_replace(
        '%file_type_list%',
        $file_type_list,
        $lang["FileTypeLimits"]
    );
}

$PHORUM["DATA"]["LANG"]["mod_user_avatar"]["AvatarLimit"] = str_replace(
    '%max_avatars%',
    $PHORUM["mod_user_avatar"]['max_avatars'],
    $lang["AvatarLimit"]
);

$PHORUM["DATA"]["TOTAL_FILES"] = count($avatars);
$PHORUM["DATA"]["TOTAL_FILE_SIZE"] = phorum_filesize($total_size);

?>
