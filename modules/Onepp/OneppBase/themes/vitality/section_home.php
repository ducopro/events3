<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<header id="<?php print $_identifier; ?>">
  
   <div class="intro-content">
      <?php if( $Id ): ?>
      <div class="brand-name">
         <?php if( $Icon): ?>
             <i class="fa fa-2x fa-<?php print $Icon; ?> wow bounceIn"></i>
             <div style="height:0.5em;"></div>
         <?php endif; ?>
         <?php print $Id; ?>
      </div>
      <hr class="colored" />
      <?php endif; ?>
      <div class="brand-name-subtext"><?php print $Name; ?></div>
      <p><?php print $Text_1; ?></p>
   </div>
   
   <?php if( $Description ): // This is the link  ?>
   <div class="scroll-down">
      <a class="btn page-scroll" href="<?php print $Description; ?>"><i class="fa fa-angle-down fa-fw"></i></a>
   </div>
   <?php endif; ?>

  <?php print $_content; // Columns need to be empty in this theme ...?>
</header>