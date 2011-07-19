{IF AVATAR_PANEL_ACTIVE}
  {VAR MENU_ITEM_CLASS 'class="current"'}
{ELSE}
  {VAR MENU_ITEM_CLASS ""}
{/IF}

<li>
  <a {MENU_ITEM_CLASS} href="{URL->CC_AVATAR}">
    {LANG->mod_user_avatar->CCMenuItem}
  </a>
</li>

