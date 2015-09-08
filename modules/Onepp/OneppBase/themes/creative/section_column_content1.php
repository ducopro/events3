<div class="<?php print $_columns; ?> text-center">
  
  <?php if($Id): ?>
    <h2 class="section-heading"><?php print $Id; ?></h2>
    <hr class="primary">  
  <?php endif; ?>
  
  <div class="service-box">

      <?php if( $Description ): // This is the link ... ?>
         <a href="<?php print $Description; ?>" class="page-scroll">
      <?php endif; ?>

      <?php if( $Icon): ?>
          <i class="fa fa-4x fa-<?php print $Icon; ?> wow bounceIn text-primary"></i>
      <?php endif; ?>
      
      <?php if( $Description ): // This is the link ... ?>
         </a>
      <?php endif; ?>
      
      <?php if( $Name): ?>
          <h3 class="section-heading"><?php print $Name; ?></h3>
      <?php endif; ?>
      
      <?php if( strip_tags($Text_1)): ?>
          <p class="muted"><?php print $Text_1; ?></p>
      <?php endif; ?>

      

      

  </div>
</div>