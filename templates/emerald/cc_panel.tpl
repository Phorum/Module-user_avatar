{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<form action="{URL->AVATAR_CONFIGURE}" method="post">
  {POST_VARS}

  <h2>{LANG->mod_user_avatar->ConfigHeadline}</h2>

  <div class="generic">

    {IF NOT NUMBER_OF_FILES 0 AND PERMISSION->AVATAR_CREATE}

      <div class="avatar_list" id="avatar_list">

        {LANG->mod_user_avatar->SelectAvatarHelp}
        <br/><br/>

        {LOOP FILES}
        <div class="avatar_list_item{IF FILES->selected} active{/IF}"
             id="avatar_list_item_{FILES->file_id}">

          {IF FILES->option_gravatar}
          <div style="float:right" class="gravatar_ref">
            <a href="http://www.gravatar.com">
              <img src="{URL->HTTP_PATH}/mods/user_avatar/images/gravatar-logo.gif"/>
              <br/>
              {LANG->mod_user_avatar->GravatarInfo}
            </a>
          </div>
          {/IF}

          <table>
            <tr>
              <td>
                <img src="{FILES->url}"
                     style="width:{MOD_USER_AVATAR->WIDTH}px;
                            height:{MOD_USER_AVATAR->HEIGHT}px"/>
              </td>

              <td>
                <label>
                  <input type="radio"
                         name="avatar"
                         value="{FILES->file_id}"
                         {IF FILES->selected}checked="checked"{/IF} />
                    {FILES->text}
                </label>
              </td>

              {IF FILES->option_avatar}
              <td>
                <label>
                  <input type="checkbox"
                         name="delete[]"
                         value="{FILES->file_id}" />
                  {LANG->Delete}
                </label>
              </td>
              {/IF}

            </tr>
          </table>
        </div>
        {/LOOP FILES}

      </div>

    {/IF}

    {IF PERMISSION->AVATAR_DISABLE}
      <div style="margin: 2em 0 0 0">
        <label>
          <input type="checkbox"
                 name="disable_avatar_display"
                 value="1"
                 {IF mod_user_avatar->disable_avatar_display}
                   checked="checked"
                 {/IF} />
          {LANG->mod_user_avatar->BlockAvatars}
        </label>
      </div>
    {/IF}

    {IF NOT NUMBER_OF_FILES 0 OR PERMISSION->AVATAR_DISABLE}
      <div style="margin: 1em 0 0 0">
        <input type="submit" value="{LANG->SaveChanges}" />
      </div>
    {/IF}

  </div>

</form>

{IF PERMISSION->AVATAR_CREATE}
  {IF URL_ENABLED OR UPLOAD_ENABLED}
    <form action="{URL->AVATAR_UPLOAD}" method="post"
          enctype="multipart/form-data"
          style="margin-top: 2em">

        {POST_VARS}

        <!-- The available width for the form. This is used to  -->
        <!-- layout the cropping interface. The value is filled -->
        <!-- by JavaScript. This is also used for checking if   -->
        <!-- the client has javascript enabled or not. If not,  -->
        <!-- then the croping interface will not be available.  -->
        <input type="hidden" id="avatar_form_width" name="form_width" value="" />

        <h2>{LANG->mod_user_avatar->UploadHeadline}</h2>

        <div id="avatar_upload_form" class="generic avatar_editor">

          <div>{LANG->mod_user_avatar->AvatarLimit}</div>

          {IF UPLOAD_ENABLED}
          <div>{LANG->FileSizeLimits} {MAX_UPLOAD_SIZE}</div>
          {/IF}

          <br />
          <table>

          {IF UPLOAD_ENABLED}
          <tr>
            <td style="width:1%">{LANG->UploadFile}:</td>
            <td><input type="file" name="newfile" size="30" /></td>
          </tr>
          {/IF}

          {IF URL_ENABLED}
          <tr>
            <td>{LANG->mod_user_avatar->RetrieveFromUrl}:</td>
            <td><input type="text" class="url" name="url" size="40"/></td>
          </tr>
          {/IF}

          </table>
          <input type="submit" value="{LANG->Submit}" />
        </div>

    </form>

    <!-- The code that will fill the form_width hidden field. -->
    <script type="text/javascript">
    //<![CDATA[
    $PJ(document).ready(function ()
    {
      // Determine the available width for the form.
      var width = $PJ('#avatar_upload_form').width();

      // A correction for the spacing that we want to use in the
      // cropping interface layout. By providing it here, the
      // template code can decide how much spacing is needed.
      width -= 20;

      // Set the hidden field value.
      $PJ('#avatar_form_width').val(width);

      // Setup form dynamics for enhancing the avatar selection interface.
      $list = $PJ('#avatar_list');
      $list.find('input[type=radio]').change(function () {
        $list.find('.avatar_list_item').removeClass('active');
        $list.find('#avatar_list_item_' + this.value).addClass('active');
      });
      $list.find('img').click(function () {
        $PJ(this)
          .closest('.avatar_list_item')
          .find('input[type=radio]')
          .attr('checked', 'checked')
          .change();
      });
    });
    // ]]>
    </script>

    <br/>
  {/IF}
{/IF}

