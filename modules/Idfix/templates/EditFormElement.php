<?php 
/**
 * $cTitle = Naam van het betreffende element
 * $cDescription = Omschrijving van het element
 * $cError = Errormessage op dit element
 * $cId = Het unieke ID van het input element
 * $cInput = De Html van het element
 * 
 */ 
 
 
 $cFormGroupClass = 'form-group';
 if ($cError) {
    $cFormGroupClass .= ' has-error';
    $cDescription = $cError . '<br />' . $cDescription;
 }
 
?>
 <div class="<?php print $cFormGroupClass;?>">
   <?php if($cTitle) print "<label for=\"{$cId}\">{$cTitle}</label>";  ?>
   <?php print $cInput; // Full input element ?>
   <?php if($cDescription) print "<p class=\"help-block\">{$cDescription}</p>";  ?>
</div>        