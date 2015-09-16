<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>

<header id="<?php print $_identifier; ?>">
  <div class="header-content">
   <div class="header-content-inner">
       <?php if( $Icon): ?>
          <h1><i class="fa fa-4x fa-<?php print $Icon; ?>"></i></h1>
       <?php endif; ?>
       <?php if($Id): ?>
          <h1><?php print $Id; ?></h1>
       <?php endif; ?>
       <?php if($Name): ?>
          <h2><?php print $Name; ?></h2>
       <?php endif; ?>
       <?php if($Id or $Name or $Icon): ?>
          <hr />
       <?php endif; ?>
       <?php if( strip_tags($Text_1)): ?>
          <p><?php print $Text_1; ?></p>
       <?php endif; ?>

       <?php if( $Description AND $Text_2 ): // This is the link and link text ?>
          <a href="<?php print $Description; ?>" class="btn btn-primary btn-xl page-scroll"><?php print $Text_2; ?></a>
       <?php endif; ?>
   </div>
</div>
</header>