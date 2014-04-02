<div class="row">
<?php 
$iCounter =0;
foreach( $aReportPanels as $cHtml):
   $bNewRow =(++$iCounter%3==1);
    if ($bNewRow) {
        print '</div><div class="row">';
    }
?>

<div class="col col-sm-4">
   <?php print $cHtml; ?>
</div>

<?php endforeach; ?>
</div>