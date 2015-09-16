<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<section id="<?php print $_identifier; ?>" class="services">
  <div class="container">
     
     <?php if($_header): ?>
      <div class="row text-center">
         <div class="col-lg-12 wow fadeIn">
            <?php print $_header; ?>
         </div>
      </div>
      <?php endif; ?>

     <div class="row content-row">
       <?php print $_content; ?>
     </div>
  </div>
</section>

