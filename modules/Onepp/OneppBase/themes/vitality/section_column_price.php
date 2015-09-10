<?php
$Text_1 = str_ireplace('<ul', '<ul class="list-group"', $Text_1);
$Text_1 = str_ireplace('<li', '<li class="list-group-item"', $Text_1);

// Calculate extra class for the featured item based on the number of columns
$cExtraClasses ='';
if( $_columncount == $_columncurrent) {
   $cExtraClasses = 'featured-last';
}
elseif($_columncurrent == 1) {
   $cExtraClasses = 'featured-first';
}
else {
   $cExtraClasses = 'featured';
}

?>
<div class="<?php print $_columns; ?>">
  <div class="pricing-item <?php print $cExtraClasses; ?>">
      <h3><?php if( $Icon): ?><i class="fa fa-<?php print $Icon; ?>"></i>&nbsp;<?php endif; ?><?php print $Id; ?></h3>
      <h5><?php print $Name; ?></h5>
      <hr class="colored" />
      <?php print $Text_1; ?>
      <?php if($Description AND $Text_2):?>
         <a href="<?php print $Description; ?>" class="btn btn-outline-dark"><?php print $Text_2; ?></a>
      <?php endif; ?>
  </div>
</div>
