<?php

// Add avatar images to Phorum messages.
function mod_user_avatar_apply_avatar_to_messages($messages)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // If the user doesn't want to show avatars, we are done.
    if (mod_user_avatar_current_user_has_permission('disable') &&
        !empty($PHORUM["user"]["mod_user_avatar"]["disable_avatar_display"])) {
        return $messages;
    }

    // If the permission settings are set to only display moderator avatars
    // if a user is moderator for the active forum, then retrieve a list
    // of moderators.
    $moderators = NULL;
    if ($PHORUM['mod_user_avatar']['permission_create'] == AVATAR_PERM_MODERATOR &&
        !empty($PHORUM['mod_user_avatar']['moderator_only_in_mod_forums'])) {
        $moderators = phorum_api_user_list_moderators();
    }

    $file_url_template = phorum_get_url(PHORUM_FILE_URL, "file=%file_id%");

    $cache = array();

    // Retrieve the data for the involved users.
    // Sometimes, the user data will already be filled in the message
    // data. For those messages, we will not retrieve fresh data.
    $user_ids = array();
    foreach ($messages as $message) {
        if (empty($message['user']) && !empty($message['user_id'])) {
            $user_ids[$message['user_id']] = $message['user_id'];
        }
    }
    $users = phorum_api_user_get($user_ids);

    // Apply avatars to all messages in the array.
    foreach ($messages as $messageid => $message)
    {
        // We start out with no avatar.
        $messages[$messageid]["MOD_USER_AVATAR"] = FALSE;

        // Only registered users can have an avatar.
        if (empty($message["user_id"]))
        {
            // We do apply the default avatar for anonymous users though.
            $url = $PHORUM['mod_user_avatar']['default_avatar'];
            $messages[$messageid]["MOD_USER_AVATAR"] = $url;

            continue;
        }

        // Use the cached avatar if we have one.
        if (isset($cache[$message["user_id"]]))
        {
            if ($cache[$message["user_id"]]) {
                $url = $cache[$message["user_id"]];
                $messages[$messageid]["MOD_USER_AVATAR"] = $url;
            }
            continue;
        }

        // Handle special permission, where the avatar is only shown
        // for moderators that actually have moderator permission for
        // the current forum.
        if ($moderators!==NULL && !isset($moderators[$message['user_id']])){
            continue;
        }

        // Retrieve the author's user information.
        // The user information is already available in the message.
        if (isset($message['user'])) {
             $author = $message['user'];
        }
        // The user information was looked up earlier successfully.
        elseif (isset($users[$message['user_id']])) {
             $author = $users[$message['user_id']];
        }
        // No user data found.
        else {
            $author = NULL;
        }

        // In case only admins can have an avatar, check if the user
        // is an admin or not.
        if ($PHORUM['mod_user_avatar']['permission_create'] == AVATAR_PERM_ADMIN) {
            if (empty($author) || empty($author['admin'])) continue;
        }

        // From here on, use the default avatar image if the user
        // has no avatar set.
        $url = $PHORUM['mod_user_avatar']['default_avatar'];
        $messages[$messageid]["MOD_USER_AVATAR"] = $url;

        // Without author information at hand, nothing can be done below here.
        if (empty($author)) {
            continue;
        }

        // If the author has no avatar enabled, we're done.
        if (empty($author["mod_user_avatar"]["avatar"]) ||
            $author["mod_user_avatar"]["avatar"] == -1) {
            $cache[$message["user_id"]] = $url; // negative caching.
            continue;
        }

        // Handle Gravatar.
        if ($author["mod_user_avatar"]["avatar"] == -2) {
            $url = mod_user_avatar_get_gravatar_url($author);
        }
        // Handle standard avatar.
        else {
            $file_id = $author["mod_user_avatar"]["avatar"];
            $url = str_replace('%file_id%', $file_id, $file_url_template);
        }

        $messages[$messageid]["MOD_USER_AVATAR"] = $url;

        // Cache the info, in case we encounter this user more
        // often in the loop.
        $cache[$message["user_id"]] = $url; // positive caching
    }

    return $messages;
}

// Add avatar images to a user's data.
function mod_user_avatar_apply_avatar_to_user($profile)
{
    global $PHORUM;

    // Setup the default avatar image as a starting point.
    $url = $PHORUM['mod_user_avatar']['default_avatar'];
    $profile['MOD_USER_AVATAR'] = $url;

    // Check if we have an avatar for this user.
    if (empty($profile["mod_user_avatar"]["avatar"]) ||
        $profile["mod_user_avatar"]["avatar"] == -1) {
        return $profile;
    }

    // Add a Gravatar to the profile data.
    if ($profile["mod_user_avatar"]["avatar"] == -2) {
        $profile["MOD_USER_AVATAR"] =
            mod_user_avatar_get_gravatar_url($profile);
        return $profile;
    }

    // Add a standard avatar to the profile data.
    $file_id = $profile['mod_user_avatar']["avatar"];
    $profile["MOD_USER_AVATAR"] = phorum_get_url(
        PHORUM_FILE_URL,
        "file=$file_id"
    );

    return $profile;
}

/**
 * Format the Gravatar URL for a user.
 *
 * @param array $user
 *     Phorum user data. This should at least contain the field "email".
 * @param string $alternative_url
 *     An alternative image URL to use in case there is no Gravatar
 *     available on the Gravatar server.
 *
 * @return string
 *     The URL for the Gravatar image.
 */
function mod_user_avatar_get_gravatar_url($user, $alternative_url = NULL)
{
    global $PHORUM;

    if (empty($PHORUM['mod_user_avatar']['gravatar_enabled'])) {
        return NULL;
    }

    $w = $PHORUM["mod_user_avatar"]["max_width"];
    $h = $PHORUM["mod_user_avatar"]["max_height"];
    $size = $w > $h ? $h : $w;

    $url = 'http://www.gravatar.com/avatar/' .
           md5(strtolower(trim($user['email']))) .
           "?s=$size";

    if ($alternative_url !== NULL) {
        $url .= 'd=' . urlencode($alternative_url);
    }

    return $url;
}

?>
