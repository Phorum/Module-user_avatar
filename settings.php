<?php

if (!defined('PHORUM_ADMIN')) return;

// save settings
if (count($_POST))
{
    $PHORUM['mod_user_avatar']['max_height']   = (int)$_POST['max_height'];
    $PHORUM['mod_user_avatar']['max_width']    = (int)$_POST['max_width'];
    $PHORUM['mod_user_avatar']['max_avatars']  = (int)$_POST['max_avatars'];
    $PHORUM['mod_user_avatar']['max_filesize'] = (int)$_POST['max_filesize'];
    $PHORUM['mod_user_avatar']['file_types']   = $_POST['file_types'];
    $PHORUM['mod_user_avatar']['permission_create'] = (int)$_POST['permission_create'];
    $PHORUM['mod_user_avatar']['moderator_only_in_mod_forums'] =
        isset($_POST['moderator_only_in_mod_forums']);
    $PHORUM['mod_user_avatar']['permission_disable'] = (int)$_POST['permission_disable'];

    require_once('./mods/user_avatar/defaults.php');

    if(empty($error)) {
        phorum_db_update_settings(array(
          'mod_user_avatar' => $PHORUM['mod_user_avatar']
        ));
        phorum_admin_okmsg('Settings Updated');
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

$frm->addbreak('Limits for uploading avatars');
$row = $frm->addrow('Maximum number of avatars per user: ', $frm->text_box('max_avatars', $PHORUM['mod_user_avatar']['max_avatars']));
$frm->addhelp($row, 'Maximum number of avatars',
    "This setting specifies how many avatars the user can upload to his
     profile. No matter how many avatars the user uploads, there will
     always be only one avatar available for selection as the active avatar.");

$frm->addrow('Maximum Height: ', $frm->text_box('max_height', $PHORUM['mod_user_avatar']['max_height']) . ' pixels');
$frm->addrow('Maximum Width: ', $frm->text_box('max_width', $PHORUM['mod_user_avatar']['max_width']) . ' pixels');
$frm->addrow('Maximum File Size: ', $frm->text_box('max_filesize', $PHORUM['mod_user_avatar']['max_filesize']) . ' KB');

$frm->addmessage('');
$frm->addbreak('Image types which a user can use as an avatar');
$types=array('gif','jpg','png');
foreach($types as $type){
    $checked = (@in_array($type, $PHORUM['mod_user_avatar']['file_types']))? 1 : 0;
    $frm->addrow($frm->checkbox('file_types[]', $type, '', $checked) . $type);
}

$frm->addmessage('');
$frm->addbreak('Permissions for the avatar module');

$choices = array(
    AVATAR_PERM_ALL       => 'All registered users',
    AVATAR_PERM_MODERATOR => 'Moderators and Phorum administrators',
    AVATAR_PERM_ADMIN     => 'Phorum administrators',
);

$frm->addrow('Who are allowed to use the avatar feature?', $frm->select_tag('permission_create', $choices, $PHORUM['mod_user_avatar']['permission_create'], 'id="moderator_perm" onchange="toggleModeratorPermission()"') . '<div id="moderator_perm_div" style="display:none">'.$frm->checkbox('moderator_only_in_mod_forums', 1, '', $PHORUM['mod_user_avatar']['moderator_only_in_mod_forums']) . 'Only show an avatar for moderators in<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;the forums which they moderate</div>');

$choices[AVATAR_PERM_NOBODY] = 'Nobody';

$row = $frm->addrow('Who can disable displaying of avatars?', $frm->select_tag('permission_disable', $choices, $PHORUM['mod_user_avatar']['permission_disable']));
$frm->addhelp($row, 'Disable displaying of avatars', "The users that have permission to disable the displaying of avatars, get an extra option in their control center to do so. Using this option, they can hide avatars for themselves when reading the forums.<br/><br/>Normally, it's best to grant this permission to all registered users, so they can choose for themselves whether to show avatars or not.");

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

