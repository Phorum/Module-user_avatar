{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

{IF PERMISSION->AVATAR_CREATE}
  <form action="{URL->ACTION}" method="post" enctype="multipart/form-data">
      {POST_VARS}

      <div class="generic">
          <h4>{LANG->mod_user_avatar->UploadHeadline}</h4>
          {IF FILE_SIZE_LIMIT}<div>{FILE_SIZE_LIMIT}</div>{/IF}
          {IF FILE_TYPE_LIMIT}<div>{FILE_TYPE_LIMIT}</div>{/IF}
          <div>{LANG->mod_user_avatar->AvatarLimit}</div>
          <br />
          <input type="file" name="newfile" size="30" />
          <input type="submit" value="{LANG->Submit}" />
      </div>

  </form>

  <br/>
{/IF}

<form action="{URL->ACTION}" method="post">
  {POST_VARS}

  {IF NOT NUMBER_OF_FILES 0 AND PERMISSION->AVATAR_CREATE}
    <table cellspacing="0" class="list" style="width:100%">
      <tr>
        <th align="left" style="white-space:nowrap">
          {LANG->Delete}
        </th>
        <th align="left" style="white-space:nowrap">
          {LANG->mod_user_avatar->SelectAvatar}
        </th>
        <th align="left" style="white-space:nowrap">
          {LANG->Filename}
        </th>
        <th align="right" style="white-space:nowrap">
          {LANG->Preview}
        </th>
      </tr>

      {LOOP FILES}
        <tr>
          <td style="vertical-align:middle" width="5%"><input type="checkbox" name="delete[]" value="{FILES->file_id}" /></td>
          <td style="vertical-align:middle"><input type="radio" name="avatar" value="{FILES->file_id}"{IF FILES->selected} checked="checked"{/IF} /></td>
          <td style="vertical-align:middle">
              <a href="{FILES->url}">{FILES->filename}</a><br/>
              {FILES->filesize}
              {IF FILES->dimensions}({FILES->dimensions}){/IF}<br/>
          </td>
          <td style="text-align:right; vertical-align:middle">
            <img src="{FILES->url}" />
          </td>
        </tr>
      {/LOOP FILES}

    </table>
  {/IF}

  {IF PERMISSION->AVATAR_DISABLE}
    <input type="checkbox" id="disable_avatar_display" name="disable_avatar_display" value="1" {IF mod_user_avatar->disable_avatar_display}checked="checked"{/IF} />
    <label for="disable_avatar_display">{LANG->mod_user_avatar->BlockAvatars}</label><br /><br />
  {/IF}

  <input type="submit" value="{LANG->SaveChanges}" />

</form>

