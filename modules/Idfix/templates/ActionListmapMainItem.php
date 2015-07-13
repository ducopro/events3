<!-- Modal <?php print $uid; ?>-->
<div class="modal" id="<?php print $uid; ?>" tabindex="-1" role="dialog" aria-labelledby="label-<?php print $uid; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title" id="label-<?php print $uid; ?>"><?php print $icon; ?> <?php print $title; ?></h4>
        <h5 class="modal-title" id="label-<?php print $uid; ?>"><?php print $fulldate; ?></h5>
      </div>

      <div class="modal-body">
       
       <div class="table-responsive">
          <table class="table">
            <thead><th>Title</th><th>Data</th>
            </thead>
            <tbody>
               <?php foreach($data as $cTitle => $cData ):?>
                  <tr><td><?php print $cTitle; ?></td><td><?php print $cData; ?></td></tr>
               <?php endforeach; ?>
            </tbody>
          </table>
       </div>
       
      </div>

      <div class="modal-footer">
        <div class="pull-left">
            <?php print $delete;?>
        </div>
        <?php print $edit;?>
        <?php print $copy;?>
        <button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- END Modal <?php print $uid; ?>-->