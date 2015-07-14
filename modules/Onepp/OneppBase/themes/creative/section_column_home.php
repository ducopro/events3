<div class="header-content">
   <div class="header-content-inner">
       <h1><?php print $Id; ?></h1>
       <hr>
       <p><?php print $Text_1; ?></p>
       <?php if( $Description AND $Name ): // This is the link and link text ?>
       <a href="<?php print $Description; ?>" class="btn btn-primary btn-xl page-scroll"><?php print $Name; ?></a>
       <?php endif; ?>
   </div>
</div>