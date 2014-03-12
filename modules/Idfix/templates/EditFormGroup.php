<!-- Start Fieldset <?php print $cTitle; ?> -->
<div class="panel <?php print $cPanelClass; ?>">
  
  <div class="panel-heading">
       <h4 class="panel-title">
           <a id="toggle-<?php print $cId; ?>" data-toggle="collapse" data-parent="#accordion" href="#<?php print $cId; ?>">
               <?php print $cIcon?>
               <?php print $cTitle?>&nbsp;
               <small><?php print $cDescription?></small>
           </a>    
       </h4>
  </div>

  <div id="<?php print $cId; ?>" class="panel-collapse collapse<?php print $cClass; ?>">
      <div class="panel-body">
        <div class="row">
          <?php print $cElements ?>
        </div>  
      </div>
  </div>

</div>
<!-- End Fieldset <?php print $cTitle?> -->

