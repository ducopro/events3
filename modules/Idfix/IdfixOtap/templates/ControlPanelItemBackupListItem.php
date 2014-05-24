<p>
<div class="btn-group">
  <button type="button" class="btn btn-primary"><?php print $name; ?></button>
  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
    <li><a href="<?php print $restore;?>"><?php print $restore_icon;?>Restore</a></li>
    <li><a href="<?php print $download;?>"><?php print $download_icon;?>Download</a></li>
    <li class="divider"></li>
    <li class="bg-danger"><a onclick="confirm('Are you sure you want to delete this backup?')" title="Delete" class="bg-danger" role="button" href="<?php print $delete;?>"><span class="text-danger"><?php print $delete_icon;?>Delete</span></a></li>
    <li class="divider"></li>
    <li><a href="#">Size: <span class="badge right"><?php print $size;?></span></a></li>
  </ul>
</div>
</p>