<style>

header {
   <?php if($BG_Url): ?>
   background-color: transparent;
   background-image: url( <?php print $BG_Url; ?>);
   <?php elseif($BG_picture): ?>
   background-color: transparent;
   background-image: url( <?php print $BG_picture['url']; ?> );
   <?php elseif($BG_color): ?>
   background-color: <?php print $BG_color; ?>;
   background-image: none;
   <?php endif; ?>   
}

</style>
<header>
  <?php print $_content; ?>
</header>