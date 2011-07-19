<?php
// ======================================================================
// This script takes care of storing an avatar in the file storage.
// The script that included this script must setup the variables:
// - $filename : the filename to use for the stored file
// - $scaled   : the scaled image (as returned by the image API
//               image scaling methods)
// ======================================================================

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once PHORUM_PATH.'/include/api/file.php';

$index_script = dirname(__FILE__) . '/index.php';

// Retrieve the current avatars.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

// Check if the user already uploaded the maximum number
// of avatars allowed. We already checked this at upload time, but
// we need to check here again to prevent a user from opening a lot
// of crop interfaces before submitting them all, in which case the
// upload check would have no effect anymore.
$max_allowed = $PHORUM["mod_user_avatar"]['max_avatars'];
if (count($avatars) >= $max_allowed)
{
    $data['error'] = str_replace(
        '%max_avatars%',
        $max_allowed,
        $lang["ErrorTooManyAvatars"]
    );
    return include $index_script;
}

// Create the file array for the file storage API.
$file = array(
    'user_id'   => $PHORUM['user']['user_id'],
    'filename'  => $filename,
    'filesize'  => strlen($scaled['image']),
    'file_data' => $scaled['image'],
    'link'      => 'avatar'
);

// Store the file.
if (!($file_ret = phorum_api_file_store($file))) {
    $data['error'] = phorum_api_strerror();
} else {
    $data['okmsg'] = $PHORUM["DATA"]["LANG"]["FileAdded"];
}

include $index_script;
?>
