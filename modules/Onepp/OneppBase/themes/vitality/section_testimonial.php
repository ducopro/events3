<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>
<section class="testimonials bg-gray" id="<?php print $_identifier; ?>">
   <div class="container wow fadeIn">
      
      <?php if($_header): ?>
      <div class="row">
         <div class="col-lg-10 col-lg-offset-1">
            <?php print $_header; ?>
         </div>
      </div>
      <?php endif; ?>
         
      <div class="row content-row">
         <div class="col-lg-10 col-lg-offset-1">
            <div class="testimonials-carousel">
               <?php print $_content; ?>
            </div>
         </div>
      </div>
   </div>
</section>    