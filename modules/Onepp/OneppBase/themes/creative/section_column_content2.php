<div class="<?php print $_columns; ?> text-center">

  <?php if( $Id): ?>
      <h2 class="section-heading"><?php print $Id; ?></h2>
      <hr class="light" />  
  <?php endif; ?>
  
  <?php if( $Icon): ?>
      <i class="fa fa-4x fa-<?php print $Icon; ?> wow bounceIn text-faded"></i>
  <?php endif; ?>
  
  <?php if( strip_tags($Text_1)): ?>
      <p class="text-faded"><?php print $Text_1; ?></p>
  <?php endif; ?>
  
  <?php if( $Description AND $Name ): // This is the link and link text ?>
     <a href="<?php print $Description; ?>" class="btn btn-default btn-xl page-scroll"><?php print $Name; ?></a>
  <?php endif; ?>

</div>
