<?php if($_image): ?>

<?php 
// Bepaal of we een popup moeten tonen
$bShowPopup = (boolean) strip_tags($Text_1);
if($bShowPopup){
   $cPopupID = 'slider-popup-'.$MainID;  
}

?>
<div class="item" style="background-image: url('<?php print $_image; ?>');">
   <div class="container-fluid">
       <div class="row">
           <div class="<?php print $_columns; ?>">
               <div class="project-details">
                   <span class="project-name"><?php print $Id; ?></span>
                   <span class="project-description"><?php print $Name; ?></span>
                   <hr class="colored" />
                   <?php if( $Description ): // This is the link ... ?>
                     <a href="<?php print $Description; ?>"  class="btn btn-outline-light">Read more <i class="fa fa-long-arrow-right fa-fw"></i></a>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </div>
</div>


<?php endif; ?>

