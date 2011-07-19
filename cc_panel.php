<?php

if (!defined('PHORUM_CONTROL_CENTER')) return;

global $PHORUM;
$lang = $PHORUM["DATA"]["LANG"]["mod_user_avatar"];

// Custom form posting actions with the avatar panel and action in
// the GET arguments. This is especially useful to get back at the
// avatar control panel when the user posts a file that exceeds
// the PHP post limit (which would nullify the POST data.)
$PHORUM["DATA"]["URL"]["AVATAR_UPLOAD"] = phorum_api_url(
    PHORUM_CONTROLCENTER_URL, "panel=avatar", "action=upload"
);
$PHORUM["DATA"]["URL"]["AVATAR_CONFIGURE"] = phorum_api_url(
    PHORUM_CONTROLCENTER_URL, "panel=avatar", "action=configure"
);

// Initialize the user's setting store for this module.
if (empty($PHORUM["user"]["mod_user_avatar"]) ||
      !is_array($PHORUM["user"]["mod_user_avatar"])) {
      $PHORUM["user"]["mod_user_avatar"] = array();
}   
if (empty($PHORUM["user"]["mod_user_avatar"]["image_info"])) {
      $PHORUM["user"]["mod_user_avatar"]["image_info"] = array();
}   

// Include the subscript that handles the current state.
if (isset($_POST['action']) && $_POST['action'] === 'crop') {
  include dirname(__FILE__) . '/cc_panel/crop.php';
} elseif (!isset($PHORUM['args']['action'])) {
  include dirname(__FILE__) . '/cc_panel/index.php';
} elseif ($PHORUM['args']['action'] === 'upload') {
  include dirname(__FILE__) . '/cc_panel/upload.php';
} elseif ($PHORUM['args']['action'] === 'configure') {
  include dirname(__FILE__) . '/cc_panel/configure.php';
}

?>
