<div class="alert alert-dismissable alert-<?php print $cType;?>">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <ul>
    <?php foreach($aMessages as $cMessage ){ print "<li>{$cMessage}</li>"; } ?>
  </ul>
</div>