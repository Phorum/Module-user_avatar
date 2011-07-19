<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.
// It also sets up some constants that we use.

// ----------------------------------------------------------------------
// THIS FILE IS NOT MEANT FOR CHANGING MODULE SETTINGS.
// USE THE MODULE SETTINGS IN THE PHORUM ADMIN FOR THAT,
// UNLESS YOU KNOW WHAT YOU ARE DOING.
// ----------------------------------------------------------------------

define('AVATAR_PERM_ALL',       1);
define('AVATAR_PERM_MODERATOR', 2);
define('AVATAR_PERM_ADMIN',     3);
define('AVATAR_PERM_NOBODY',    4);

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_user_avatar"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"] = array();

if (empty($GLOBALS["PHORUM"]["mod_user_avatar"]["max_avatars"]) )
    $GLOBALS["PHORUM"]["mod_user_avatar"]["max_avatars"] = 5;

if (empty($GLOBALS["PHORUM"]["mod_user_avatar"]["max_height"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["max_height"] = 100;

if (empty($GLOBALS["PHORUM"]["mod_user_avatar"]["max_width"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["max_width"] = 100;

if (!isset($GLOBALS["PHORUM"]["mod_user_avatar"]["default_avatar"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["default_avatar"] = '';

if (empty($GLOBALS["PHORUM"]["mod_user_avatar"]["permission_create"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["permission_create"] = AVATAR_PERM_ALL;

if (!isset($GLOBALS["PHORUM"]["mod_user_avatar"]["moderator_only_in_mod_forums"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["moderator_only_in_mod_forums"] = 0;

if (empty($GLOBALS["PHORUM"]["mod_user_avatar"]["permission_disable"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["permission_disable"] = AVATAR_PERM_ALL;

if (!isset($GLOBALS["PHORUM"]["mod_user_avatar"]["upload_enabled"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["upload_enabled"] = 1;

if (!isset($GLOBALS["PHORUM"]["mod_user_avatar"]["url_enabled"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["url_enabled"] = 1;

if (!isset($GLOBALS["PHORUM"]["mod_user_avatar"]["gravatar_enabled"]))
    $GLOBALS["PHORUM"]["mod_user_avatar"]["gravatar_enabled"] = 1;
?>
