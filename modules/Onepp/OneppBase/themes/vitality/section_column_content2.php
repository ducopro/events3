 <div class="<?php print $_columns; ?> wow fadeIn" data-wow-delay=".2s">
     <div class="media">
        
        <?php if( $Icon): ?>
          <div class="pull-left">
            <i class="fa fa-<?php print $Icon; ?>"></i>
          </div>
        <?php endif; ?>
        
        <div class="media-body">
           <?php if($Id): ?>
               <h3 class="media-heading"><?php print $Id; ?></h3>
           <?php endif; ?>
      
           <?php if( $Description ): // This is the link ... ?>
               <a href="<?php print $Description; ?>" class="page-scroll">
           <?php endif; ?>
              <?php if( $Name): ?>
                  <h5><?php print $Name; ?></h5>
              <?php endif; ?>
           <?php if( $Description ): // This is the link ... ?>
               </a>
           <?php endif; ?>
      
           <?php if( strip_tags($Text_1)): ?>
             <?php print $Text_1; ?>
           <?php endif; ?>
        </div>     
     </div>
 </div>