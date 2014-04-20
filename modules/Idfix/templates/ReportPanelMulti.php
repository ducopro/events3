<?php
/*
The template variables we have under our sleeve
$aTemplateVars = compact('aData', 'aRollUp', 'aConfig', 'cJs', 'cReportID');*/
?>

<?php if($cJs): ?>

    <?php print $cJs; ?>
    <div style="overflow:hidden;" id="<?php print $cReportID; ?>"></div>

<?php else: ?>


    <table class="table  table-striped table-hover table-condensed table-bordered">
    
        <thead>
            <th>Group</th>
            <th>Value</th>
        </thead>
        
        <tbody>
            
            <?php 
            foreach($aData as $aRow){
               print "<tr><td>{$aRow['separate']}</td><td>{$aRow['data']}</td></tr>"; 
            };?>
            
            <tr>
                <td><strong>Total</strong></td>
                <td><strong><?php print $aRollUp['data']?></strong></td>
            </tr>
        
        </tbody>
    
    </table>



<?php endif; ?>