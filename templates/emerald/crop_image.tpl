{VAR CROP_BACKGROUND "black"}

<form action="" method="post">

  {POST_VARS}

  <input type="hidden" name="action" value="crop" />
  <input type="hidden" name="image_id" value="{CROP_FILE_ID}" />
  <input type="hidden" id="crop_x" name="x" value="0" />
  <input type="hidden" id="crop_y" name="y" value="0" />
  <input type="hidden" id="crop_w" name="w" value="{AVATAR_MAX_WIDTH}" />
  <input type="hidden" id="crop_h" name="h" value="{AVATAR_MAX_HEIGHT}" />

  <h2>{LANG->mod_user_avatar->UploadHeadline}</h2>

  <div class="generic avatar_editor">

    <p>{LANG->mod_user_avatar->CropInfo}</p>

    <table>
      <tr>
        <td>
          <img src="{CROP_FILE_URL}" id="avatar_cropbox" />
        </td>
        <td>
          <div style="margin-right : 10px;
                      width        : {AVATAR_MAX_WIDTH}px;
                      height       : {AVATAR_MAX_HEIGHT}px;
                      overflow     : hidden;
                      background   : {CROP_BACKGROUND}">
            <img src="{CROP_FILE_URL}" id="avatar_preview" />
          </div>
          <input type="submit" name="commit" value="{LANG->Submit}"
                 style="width:{AVATAR_MAX_WIDTH}px; margin-top:5px"/><br/>
          <input type="submit" name="cancel" value="{LANG->Cancel}"
                 style="width:{AVATAR_MAX_WIDTH}px; margin-top:5px"/>
        </td>
      </tr>
    </table>


  </div>

</form>

<script src="{URL->HTTP_PATH}/mods/user_avatar/Jcrop/js/jquery.Jcrop.js"
        type="text/javascript"></script>

<script type="text/javascript">
//<![CDATA[

  $PJ(document).ready(function ()
  {
    $PJ('head').append('<link rel="stylesheet" href="{URL->HTTP_PATH}/mods/user_avatar/Jcrop/css/jquery.Jcrop.css" type="text/css"/>');

    $PJ('#avatar_cropbox').Jcrop({
      onChange    : showPreview,
      onSelect    : showPreview,
      aspectRatio : {AVATAR_MAX_WIDTH} / {AVATAR_MAX_HEIGHT},
      setSelect   : [ 0, 0, 100, 100],
      bgColor     : '{CROP_BACKGROUND}'
    });
  });

  // Our simple event handler, called from onChange and onSelect
  // event handlers, as per the Jcrop invocation above
  function showPreview(coords)
  {
    var $img = $PJ('#avatar_cropbox');
    var w = $img.width();
    var h = $img.height();

    if (parseInt(coords.w, 10) > 0)
    {
      var rx = {AVATAR_MAX_WIDTH}  / coords.w;
      var ry = {AVATAR_MAX_HEIGHT} / coords.h;

      $PJ('#avatar_preview').css({
        width: Math.round(rx * w) + 'px',
        height: Math.round(ry * h) + 'px',
        marginLeft: '-' + Math.round(rx * coords.x) + 'px',
        marginTop: '-' + Math.round(ry * coords.y) + 'px'
      });

      $PJ('#crop_x').val(coords.x);
      $PJ('#crop_y').val(coords.y);
      $PJ('#crop_w').val(coords.w);
      $PJ('#crop_h').val(coords.h);
    }
  }

// ]]>
</script>
