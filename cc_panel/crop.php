<?php
// ======================================================================
// This script handles processing the data posted from the avatar
// cropping interface.
// ======================================================================

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once PHORUM_PATH.'/include/api/file.php';
require_once PHORUM_PATH.'/include/api/image.php';

$index_script = dirname(__FILE__) . '/index.php';

// Find the image that is being cropped. Return if:
// - no image_id is found in the POST data
// - no image exists for the provided image_id
// - an image exists, but it is not a temp file for this module
// - an image exists, but it is not owned by this user
if (!isset($_POST['image_id']) ||
    !($image = phorum_api_file_retrieve($_POST['image_id'])) ||
    $image['link'] !== 'avatar_tmp' ||
    $image['user_id'] !== $PHORUM['user']['user_id']) {
  return include $index_script;
}

// When the user hit the cancel button, then clean up the temporary
// image file from the file storage and return to the avatar index page.
if (!empty($_POST['cancel']))
{
  phorum_api_file_delete($image['file_id']);
  return include $index_script;
}

// Crop the image to the requested avatar.
$scaled = phorum_api_image_clip(
    $image['file_data'],
    (int) $_POST['x'],
    (int) $_POST['y'],
    (int) $_POST['w'],
    (int) $_POST['h'],
    $PHORUM["mod_user_avatar"]["max_width"],
    $PHORUM["mod_user_avatar"]["max_height"]
);
if ($scaled === FALSE) {
    $data['error'] = $lang['ErrorProcessingImage'] . ': ' .
                     phorum_api_error_message();
    return include $index_script;
}

// Generate the filename to use for the stored file.
$path = pathinfo($image['filename']);
$filename = $path['filename'] . '.jpg'; // jpg is enforced by image API

// Delete the temporary file.
phorum_api_file_delete($image['file_id']);

return include dirname(__FILE__) . '/store_scaled_avatar.php';

?>
