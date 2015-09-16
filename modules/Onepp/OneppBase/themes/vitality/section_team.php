<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>


<section class="bg-gray" id="<?php print $_identifier; ?>">
  <div class="container text-center wow fadeIn">
     
     <?php if($_header): ?>
      <div class="row text-center">
         <div class="col-lg-12 wow fadeIn">
            <?php print $_header; ?>
         </div>
      </div>
      <?php endif; ?>

      <div class="row content-row">
          <div class="col-lg-12">
             <div class="about-carousel">
                <?php print $_content; ?>
             </div>
         </div>
     </div>
  </div>
</section>
