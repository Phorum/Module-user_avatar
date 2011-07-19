<?php
// ======================================================================
// This script handles processing uploaded avatar images.
//
// If the image is smaller than or equal in size as the defined avatar
// dimensions, then the image will be used directly. When a larger image
// is uploaded, then an interface will be presented to the user for
// selecting the part of the image that must be used as the avatar.
// ======================================================================

if (!defined('PHORUM_CONTROL_CENTER')) return;

require_once PHORUM_PATH.'/include/api/file.php';
require_once PHORUM_PATH.'/include/api/image.php';
require_once PHORUM_PATH.'/include/api/http_get.php';

$index_script = dirname(__FILE__) . '/index.php';

// Check if the user is allowed to upload avatars.
if (empty($PHORUM['DATA']['PERMISSION']['AVATAR_CREATE'])) {
    return include $index_script;
}

// Retrieve the current avatars.
$avatars = phorum_api_file_list('avatar', $PHORUM["user"]["user_id"], NULL);

// Check if the user already uploaded the maximum number
// of avatars allowed.
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

// Storage for the image to process.
$image = NULL;

// Check if a URL is provided for downloading the image from, unless
// a file was uploaded via the file upload form.
if ((empty($_FILES) || $_FILES['newfile']['size'] == 0) &&
    isset($_POST['url']) &&
    trim($_POST['url']) !== '' &&
    $PHORUM['mod_user_avatar']['url_enabled'])
{
    $url = trim($_POST['url']);
    $image = phorum_api_http_get($url);

    if (!$image) {
        $data['error'] = $lang['ErrorUrlUploadFailed'] . ': ' .
                         phorum_api_error_message();
        return include $index_script;
    }

}

// If no file was uploaded via a URL, then a file must have been
// uploaded via the file upload form.
if (!$image)
{
    // Check if a file was uploaded. If not, then the uploading probably
    // failed (e.g. because the PHP post limit was exceeded).
    if (empty($_FILES) ||
        empty($_FILES['newfile']['size']) ||
        !$PHORUM['mod_user_avatar']['upload_enabled']) {
        $data['error'] = $lang['ErrorUploadFailed'];
        return include $index_script;
    }

    // Check for upload hack attempts. We silently redirect to the index
    // if we find a file that was not uploaded in this request. 
    if (!is_uploaded_file($_FILES['newfile']['tmp_name'])) {
        return include $index_script;
    }

    // Load the image file contents.
    $image = file_get_contents($_FILES['newfile']['tmp_name']);
}

// Retrieve info about the image file and check if we are willing to
// process the file as an avatar. Rules are:
// - retrieving image info must succeed
// - the image must not have a width or height of zero
// - the file must be a JPEG, GIF or PNG image
$info = phorum_api_image_info($image);
if ($info === FALSE || $info['width']  == 0 || $info['height'] == 0 || (
    $info['type'] !== IMAGETYPE_JPEG &&
    $info['type'] !== IMAGETYPE_GIF  &&
    $info['type'] !== IMAGETYPE_PNG
)) {
    $data['error'] = $lang['ErrorNotASupportedImageFormat'];
    return include $index_script;
}

// Determine if the image can be scaled directly to the avatar dimensions.
$do_scale = FALSE;

// Check if the image file is smaller than or close to the configured
// avatar dimensions (max 50% derivation), using the same aspect
// ratio (max 10% derivation). If yes, then the image will be used directly.
$max_width  = $PHORUM["mod_user_avatar"]["max_width"];
$max_height = $PHORUM["mod_user_avatar"]["max_height"];
if ($info['width']  <= $max_width  * 1.5 &&
    $info['height'] <= $max_height * 1.5)
{
    $ratio = $max_width / $max_height;
    $ratio_a = $ratio * 0.9;
    $ratio_b = $ratio * 1.1;

    $ratio_image = $info['width'] / $info['height'];

    if ($ratio_image >= $ratio_a && $ratio_image <= $ratio_b) {
      $do_scale = TRUE;
    }
}

// If a "form_width" parameter was posted with an empty value, then the client
// does not have javascript enabled. Cropping the image will not be possible.
// In such case, we will fallback to always scale the image to the
// avatar dimensions, without bothering about the size and dimensions.
if (empty($_POST['form_width'])) {
    $do_scale = TRUE;
}
// If a "form_width" parameter was posted, then check if the available
// form width does allow for a cropping interface to be laid out. If not,
// then use direct scaling. The form width is considered too small when
// the available width for the original image (on which the cropping is
// done by the user) is smaller than the width of the avatar.
elseif ($_POST['form_width'] - $max_width * 2 < 0) {
    $do_scale = TRUE;
}

// Handle direct scaling if we decided to do so above.
if ($do_scale)
{
    $scaled = phorum_api_image_clip(
        $image, 0, 0, NULL, NULL, 
        $max_width, $max_height
    );
    if ($scaled === FALSE) {
        $data['error'] = $lang['ErrorProcessingImage'] . ': ' .
                         phorum_api_error_message();
        return include $index_script;
    }

    // Generate the filename to use for the stored file.
    $path = pathinfo($_FILES['newfile']['name']);
    $filename = $path['filename'] . '.jpg'; // jpg is enforced by image API

    return include dirname(__FILE__) . '/store_scaled_avatar.php';
}

// By the time we get here, we were unable to directly store the avatar.
// The image is too large. Switch to the Jcrop interface to let the
// user tell what part of the image to use for the avatar.

// Decide what the layout of the cropping interface will have to
// look like. The GUI has posted the available width in the form_width
// parameter. The interface that we will create looks like this:
//
//  <---------- form width ---------------------------------->
//  +---------------------------------------------+ +---------+ ^
//  |                                             | |  THUMB  | |
//  |                                             | |  NAIL   |height
//  |                                             | |  IMAGE  | |
//  |                                             | +---------+ v
//  |               ORIGINAL IMAGE                | <--width-->
//  |                                             | 
//  |                                             | 
//  |                                             | 
//  |                                             | 
//  |                                             | 
//  +---------------------------------------------+

$form_width = (int) $_POST['form_width'];
$available_width = $form_width - $max_width;

// Scale the original image down if it is larger than the
// available space. If the original image is already
// smaller than the available space, we still call the
// thumbnail code, to normalize the image to the JPG format.
$scale_width = $available_width > $info['width']
             ? $info['width'] : $available_width;
$scaled = phorum_api_image_thumbnail($image, $scale_width, NULL);
if ($scaled === FALSE) {
    $data['error'] = $lang['ErrorProcessingImage'] . ': ' .
                     phorum_api_error_message();
    return include $index_script;
}
$image = $scaled['image'];

// Generate the filename to use for the stored file.
$path = pathinfo($_FILES['newfile']['name']);
$filename = $path['filename'] . '.jpg'; // jpg is enforced by image API

// Store the, possibly scaled down, image as a temporary file
// in the file storage.

// Create the file array for the file storage API.
$file = array(
    'user_id'   => $PHORUM['user']['user_id'],
    'filename'  => $filename,
    'filesize'  => strlen($image),
    'file_data' => $image,
    'link'      => 'avatar_tmp'
);

// Store the file.
if (!($crop_file = phorum_api_file_store($file))) {
    $data['error'] = phorum_api_strerror();
    return include $index_script;
}

// Setup template data for the cropping editor.
$PHORUM['DATA']['AVATAR_MAX_WIDTH']  = $max_width;
$PHORUM['DATA']['AVATAR_MAX_HEIGHT'] = $max_height;
$PHORUM['DATA']['CROP_FILE_ID']      = $crop_file['file_id'];
$PHORUM['DATA']['CROP_FILE_URL']     = phorum_get_url(
    PHORUM_FILE_URL,
    "file=" . $crop_file['file_id'],
    "filename=" . urlencode($crop_file['filename'])
);

$data['template'] = 'user_avatar::crop_image';

?>
