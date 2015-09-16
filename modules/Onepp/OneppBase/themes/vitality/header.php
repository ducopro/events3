<?php if( $Icon): ?>
   <i class="fa fa-4x fa-<?php print $Icon; ?>"></i>
<?php endif; ?>
<?php if($Id): ?>
   <h2><?php print $Id; ?></h2>
   <hr class="colored" />
<?php endif; ?>
<?php if( $Name): ?>
   <h3><?php print $Name; ?></h3>
<?php endif; ?>
<?php if( strip_tags($Text_1)): ?>
    <p><?php print $Text_1; ?></p>
<?php endif; ?>

<?php if( $Description and $Text_2 ): // This is the link ... ?>
   <a class="btn btn-outline-dark page-scroll" href="<?php print $Description; ?>" ><?php print $Text_2; ?></a>
 <?php endif; ?>