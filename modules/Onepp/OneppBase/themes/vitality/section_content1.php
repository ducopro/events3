<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<section id="<?php print $_identifier; ?>">
  <div class="container-fluid">
     <div class="row text-center">
       <div class="col-lg-12 wow fadeIn">
              <?php if( $Icon): ?>
                <i class="fa fa-4x fa-<?php print $Icon; ?>"></i>
              <?php endif; ?>
              <?php if($Id): ?>
                  <h1><?php print $Id; ?></h1>
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

     <div class="row text-center content-row">
       <?php print $_content; ?>
     </div>
  </div>
</section>
