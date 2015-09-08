<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>


<section class="bg-gray" id="<?php print $_identifier; ?>">
  <div class="container text-center wow fadeIn">
     <div class="row">
       <div class="col-lg-12">
              <?php if( $Icon): ?>
                <i class="fa fa-4x fa-<?php print $Icon; ?>"></i>
              <?php endif; ?>
              <?php if($Id): ?>
                  <h2><?php print $Id; ?></h2>
              <?php endif; ?>
              <?php if( $Name): ?>
                  <h3><?php print $Name; ?></h3>
              <?php endif; ?>
              <?php if( strip_tags($Text_1)): ?>
                <p><?php print $Text_1; ?></p>
              <?php endif; ?>
           <hr class="colored" />
           <?php if( $Description and $Text_2 ): // This is the link ... ?>
               <a class="btn btn-outline-dark page-scroll" href="<?php print $Description; ?>" ><?php print $Text_2; ?></a>
           <?php endif; ?>

       </div>
     </div>

      <div class="row content-row">
          <div class="col-lg-12">
             <div class="about-carousel">
                <?php print $_content; ?>
             </div>
         </div>
     </div>
  </div>
</section>
