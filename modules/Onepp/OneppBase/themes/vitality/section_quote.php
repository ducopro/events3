<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>


<aside class="cta-quote" id="<?php print $_identifier; ?>">
  <div class="container wow fadeIn">
     <div class="row">
       <div class="col-md-10 col-md-offset-1">
              
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
             <span class="quote"><?php print $Text_1; ?></span>
           <?php endif; ?>
           <hr class="colored" />
           <?php if( $Description and $Text_2 ): // This is the link ... ?>
               <a class="btn btn-outline-light page-scroll" href="<?php print $Description; ?>" ><?php print $Text_2; ?></a>
           <?php endif; ?>

       </div>
     </div>

  </div>
</aside>