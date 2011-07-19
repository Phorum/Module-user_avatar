<?php

if (!defined('PHORUM_ADMIN')) return;

// save settings
if (count($_POST))
{
    $PHORUM['mod_user_avatar']['max_height'] =
        (int)$_POST['max_height'];
    $PHORUM['mod_user_avatar']['max_width'] =
        (int)$_POST['max_width'];
    $PHORUM['mod_user_avatar']['default_avatar'] =
        trim($_POST['default_avatar']);

    $PHORUM['mod_user_avatar']['max_avatars'] =
        (int)$_POST['max_avatars'];
    $PHORUM['mod_user_avatar']['permission_create'] =
        (int)$_POST['permission_create'];
    $PHORUM['mod_user_avatar']['moderator_only_in_mod_forums'] =
        isset($_POST['moderator_only_in_mod_forums']);
    $PHORUM['mod_user_avatar']['permission_disable'] =
        (int)$_POST['permission_disable'];
    $PHORUM['mod_user_avatar']['upload_enabled'] =
        isset($_POST['upload_enabled']);
    $PHORUM['mod_user_avatar']['url_enabled'] =
        isset($_POST['url_enabled']);
    $PHORUM['mod_user_avatar']['gravatar_enabled'] =
        isset($_POST['gravatar_enabled']);

    require_once('./mods/user_avatar/defaults.php');

    if(empty($error)) {
        phorum_db_update_settings(array(
          'mod_user_avatar' => $PHORUM['mod_user_avatar']
        ));
        phorum_admin_okmsg('Settings updated successfully');
    }
}

require_once('./mods/user_avatar/defaults.php');

include_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ('', 'post', 'Save');
$frm->hidden('module', 'modsettings');
$frm->hidden('mod', 'user_avatar');
$frm->addbreak('User avatar module settings');

$frm->addmessage(
    "<strong>Note:</strong> Be sure to read the included
     <a href=\"./mods/user_avatar/README\" target=\"_new\">README</a>
     file for more information on the necessary template changes for this
     module. If you do not update the templates, then the avatars
     will not be visible.");

$frm->addbreak('Avatar image configuration');

$frm->addrow(
    'Avatar height: ',
    $frm->text_box('max_height', $PHORUM['mod_user_avatar']['max_height']) .
    ' pixels'
);
$frm->addrow(
    'Avatar width: ',
    $frm->text_box('max_width', $PHORUM['mod_user_avatar']['max_width']) .
    ' pixels'
);

$row = $frm->addrow(
    'Default avatar image, in case the user did not set an avatar',
    $frm->text_box(
        'default_avatar',
        $PHORUM['mod_user_avatar']['default_avatar'],
        40
    )
);
$frm->addhelp($row,
    'Default avatar image',
    'This option holds the path or the URL for the default avatar image.
     The value of this field is assigned to the {SOMETHING->MOD_USER_AVATAR}
     template variables in case the user is an anonymous user or in case
     the user did not set an avatar.<br/>
     </br>
     It is okay to leave this field empty. When you do, it is possible to
     use a conditional construction in your templates:<br/>
     <br/>
     {IF SOMETHING->MOD_USER_AVATAR}<br/>
       ...<br/>
     {/IF}'
);

$frm->addmessage('');
$frm->addbreak('Permissions for the avatar module');

$choices = array(
    AVATAR_PERM_ALL       => 'All registered users',
    AVATAR_PERM_MODERATOR => 'Moderators and Phorum administrators',
    AVATAR_PERM_ADMIN     => 'Phorum administrators',
);

$frm->addrow(
    'Who are allowed to use the avatar feature?',
    $frm->select_tag(
        'permission_create', $choices,
        $PHORUM['mod_user_avatar']['permission_create'],
        'id="moderator_perm" onchange="toggleModeratorPermission()"'
    ) .
    '<div id="moderator_perm_div" style="display:none">' .
    $frm->checkbox(
        'moderator_only_in_mod_forums', 1, '',
        $PHORUM['mod_user_avatar']['moderator_only_in_mod_forums']
    ) .
    'Only show an avatar for moderators in<br/>&nbsp;&nbsp;&nbsp;' .
    '&nbsp;&nbsp;the forums which they moderate</div>'
);

$row = $frm->addrow(
    'Maximum number of uploaded avatars per user: ',
    $frm->text_box('max_avatars', $PHORUM['mod_user_avatar']['max_avatars'])
);
$frm->addhelp($row, 'Maximum number of avatars',
    "This setting specifies how many avatars the user can upload to his
     profile. No matter how many avatars the user uploads, there will
     always be only one avatar available for selection as the active avatar.");

$row = $frm->addrow(
    'Can users make use of <a href="http://www.gravatar.com" ' .
    'target="_new">Gravatars</a>?',
    $frm->checkbox(
        'gravatar_enabled', 1, 'Yes',
        $PHORUM['mod_user_avatar']['gravatar_enabled']
    )
);
$frm->addhelp($row, 'Can users make use of Gravatars',
    'The website gravatar.com provides a service where users can
     centrally register an avatar that can be used by third party
     websites like this one. The avatar image is directly linked
     to the email address of the user.<br/>
     <br/>
     If you enable this feature, then Gravatar is added to
     the list of avialable avatars in the user\'s control center.'
);

$row = $frm->addrow(
    'Can users add avatars by uploading a local file?',
    $frm->checkbox(
        'upload_enabled', 1, 'Yes',
        $PHORUM['mod_user_avatar']['upload_enabled']
    )
);

$row = $frm->addrow(
    'Can users add avatars by providing an image URL?',
    $frm->checkbox(
        'url_enabled', 1, 'Yes',
        $PHORUM['mod_user_avatar']['url_enabled']
    )
);
$frm->addhelp($row,
    'Can users upload avatars by providing an image URL?',
    'When this option is enabled, then users can enter the URL of an
     image. The avatar script will load the image to the server.<br/>
     </br>
     For this feature to work, the hosting platform must support the
     Phorum http_get API. Check the bottom of this settings page
     for some checks. If the download test fails, then it is very
     likely that this option will not work.'
);

$choices[AVATAR_PERM_NOBODY] = 'Nobody';

$row = $frm->addrow(
    'Who can disable displaying of (other user\'s) avatars?',
    $frm->select_tag(
        'permission_disable', $choices,
        $PHORUM['mod_user_avatar']['permission_disable']
    )
);
$frm->addhelp($row, 'Disable displaying of avatars',
    'The users that have permission to disable the displaying of
     other people\'s avatars, get an extra option in their control
     center to do so. Using this option, they can hide avatars when
     reading the forums.<br/>
     <br/>
     Normally, it is best to grant this permission to all registered users,
     so they can choose for themselves whether to show avatars or not.
     A good reason for not granting this permission could be a site
     design in which the avatars play an important role.'
);

$frm->addmessage("");

$frm->addbreak("Debugging problems");

include './include/api/http_get.php';
$row = $frm->addrow(
    "Platform support for the http_get API layer",
    phorum_api_http_get_supported() ? 'OK' : 'NOT OK'
);
$frm->addhelp($row,
    "http_get platform support",
    "The hosting platform must support downloading files via HTTP,
     in case you want to allow your users to upload an image by providing
     the image URL. The hosting platform must support one of the
     following features to make this work:<br/>
     <ul>
       <li>The \"curl\" PHP module must be loaded or</li>
       <li>The \"sockets\" PHP module must be loaded or</li>
       <li>The PHP setting \"allow_url_fopen\" must be enabled</li>
     </ul>
     Please contact your hosting provider if this check returns \"NOT OK\"."
);

$page = phorum_api_http_get("http://www.google.com");
$row = $frm->addrow(
    "Download test (tries to load http://www.google.com)",
    $page && strstr($page, '<html>') ? 'OK' : 'NOT OK'
);
$frm->addhelp($row,
    "Download test",
    "This check tries to download the Google homepage via the
     http_get API. If the platform support check returns \"OK\",
     but this check returns \"NOT OK\", then the hosting platform
     might be blocking outgoing HTTP connections from the
     webserver.<br/>
     <br/>
     Please contact your hosting provider if this check returns \"NOT OK\"."
);

include './include/api/image.php';
$method = phorum_api_image_supported();
$row = $frm->addrow(
    "Platform support for the image API layer",
    $method ? "OK (using method \"$method\")" : "NOT OK"
);
$frm->addhelp($row,
    "image scaling platform support",
    "The hosting platform must support image scaling, in order to allow
     users to scale their avatar images.
     The hosting platform must support one of the following features
     to make this work:<br/>
     <ul>
       <li>The \"gd\" PHP module must be loaded or</li>
       <li>The \"imagick\" PHP module must be loaded or</li>
       <li>The ImageMagick \"convert\" application must be installed</li>
     </ul>
     Please contact your hosting provider if this check returns \"NOT OK\"."
);


$frm->addmessage('');
$frm->show();

?>

<script type="text/javascript">
//<![CDATA[
function toggleModeratorPermission() {
    var d = document.getElementById('moderator_perm_div');
    var s = document.getElementById('moderator_perm');

    var sel = s.selectedIndex;
    var item = s.options[sel].value;
    if (item == <?php print AVATAR_PERM_MODERATOR ?>) {
        d.style.display = 'block';
    } else {
        d.style.display = 'none';
    }
}

toggleModeratorPermission();
// ]]>
</script>

