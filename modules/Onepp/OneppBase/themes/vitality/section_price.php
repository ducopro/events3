<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<section class="pricing bg-gray" id="<?php print $_identifier; ?>">
  <div class="container wow fadeIn">
     
      <?php if($_header): ?>
      <div class="row text-center">
         <div class="col-lg-12 wow fadeIn">
            <?php print $_header; ?>
         </div>
      </div>
      <?php endif; ?>

     <div class="row text-center content-row">
       <?php print $_content; ?>
     </div>
  </div>
</section>
