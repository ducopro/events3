<?php if($_image): ?>

<div class="item" style="background-image: url('<?php print $_image; ?>');">
   <div class="container-fluid">
       <div class="row">
           <div class="<?php print $_columns; ?>">
               <div class="project-details">
                   <span class="project-name"><?php print $Id; ?></span>
                   <span class="project-description"><?php print $Name; ?></span>
                   <hr class="colored" />
                   <?php if( strip_tags($Text_1) ): // This is the extra info ..... ?>
                     <a href="#<?php print $_popup_id;?>" data-toggle="modal" class="btn btn-outline-light">Info <i class="fa fa-long-arrow-right fa-fw"></i></a>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </div>
</div>


<?php endif; ?>

