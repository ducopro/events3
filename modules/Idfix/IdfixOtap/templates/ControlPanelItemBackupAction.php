<a class="btn btn-warning btn-block" role="button" href="<?php print $new;?>"><?php print $icon_new;?>Create Backup</a>

<p>
    <div class="well">
        <form method="POST" class="form-inline" role="form" enctype="multipart/form-data" action="<?php print $upload; ?>">
          <input name="upload" type="file" class="form-control" >
          <button type="submit" class="btn btn-default btn-block"><?php print $icon_upload; ?>Upload</button>
        </form>
    </div>
</p>