<?php 
$iCounter =0;
foreach( $aReportPanels as $cHtml):
    $bNewRow =($iCounter%3==0);
    $iCounter++;
    if ($bNewRow) {
        print '<div class="row">';
    }
?>

<div class="col col-sm-4">
   <?php print $cHtml; ?>
</div>

<?php 
    if ($bNewRow) {
        print '</div>';
    }

endforeach; ?>