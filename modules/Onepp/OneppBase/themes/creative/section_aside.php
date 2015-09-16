<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<aside class="bg-dark" id="<?php print $_identifier; ?>">
 
  <div class="container text-center">
      <div class="call-to-action">
       <?php if($Id or $Icon): ?>
          <h2>
             <?php if($Icon): ?>
                <i class="fa fa-<?php print $Icon; ?>"></i>&nbsp;
             <?php endif; ?>
             <?php print $Id; ?>
          </h2>
       <?php endif; ?>
       <?php if($Name): ?>
          <h3><?php print $Name; ?></h3>
       <?php endif; ?>
       <?php if( strip_tags($Text_1)): ?>
          <p><?php print $Text_1; ?></p>
       <?php endif; ?>
       <?php if( $Description AND $Text_2 ): // This is the link and link text ?>
          <a href="<?php print $Description; ?>" class="btn btn-default btn-xl wow tada page-scroll"><?php print $Text_2; ?></a>
       <?php endif; ?>
      </div>
  </div>
  
  
  <?php if(trim(strip_tags($_content))): ?>
  <div class="container content">
      <div class="row">
      <?php print $_content; ?>
      </div>
  </div>
  <?php endif; ?>
  
</aside>

