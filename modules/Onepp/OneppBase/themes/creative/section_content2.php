<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<section class="bg-primary onepp-content2" id="<?php print $_identifier; ?>">
   
   <?php if(trim(strip_tags($_header))): ?>
   <div class="container">
      <div class="row">
          <div class="col-lg-12 text-center">
             <?php print $_header; ?>
          </div>
      </div>
  </div>
  <?php endif; ?>
  
  <?php if(trim(strip_tags($_content))): ?>
  <div class="container">
      <div class="row">
      <?php print $_content; ?>
      </div>
  </div>
  <?php endif; ?>
  
</section>