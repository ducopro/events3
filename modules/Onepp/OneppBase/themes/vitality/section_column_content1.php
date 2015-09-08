 <div class="<?php print $_columns; ?> wow fadeIn" data-wow-delay=".2s">
     <div class="about-content">
        <?php if( $Icon): ?>
          <i class="fa fa-4x fa-<?php print $Icon; ?>"></i>
        <?php endif; ?>
        <?php if($Id): ?>
            <h3><?php print $Id; ?></h3>
        <?php endif; ?>

        <?php if( $Description ): // This is the link ... ?>
            <a href="<?php print $Description; ?>" class="page-scroll">
        <?php endif; ?>
           <?php if( $Name): ?>
               <h4><?php print $Name; ?></h4>
           <?php endif; ?>
        <?php if( $Description ): // This is the link ... ?>
            </a>
        <?php endif; ?>

        <?php if( strip_tags($Text_1)): ?>
          <p><?php print $Text_1; ?></p>
        <?php endif; ?>
     </div>
 </div>
   


