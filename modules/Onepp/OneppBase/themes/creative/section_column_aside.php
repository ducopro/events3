<div class="<?php print $_columns; ?> text-center">

  <?php if( $Id): ?>
      <h2 class="section-heading">
         <?php if( $Icon): ?>
            <i class="fa fa-<?php print $Icon; ?> wow bounceIn text-faded"></i>
         <?php endif; ?>
         <?php print $Id; ?>
      </h2>
  <?php endif; ?>
  <?php if( $Name): ?>
      <h3 class="section-heading"><?php print $Name; ?></h3>
  <?php endif; ?>
  <?php if( strip_tags($Text_1)): ?>
      <p class="text-faded"><?php print $Text_1; ?>
      <?php if( $_smi_list) print $_smi_list; ?>
      </p>
  <?php endif; ?>
  <?php if( $Description AND $Text_2 ): // This is the link and link text ?>
     <a href="<?php print $Description; ?>" class="btn btn-default btn-xl wow fadeIn page-scroll"><?php print $Text_2; ?></a>
  <?php endif; ?>

</div>
