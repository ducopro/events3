<?php

/**
 * $aHead
 * $aBody
 */

?>
 <?php //print_r(get_defined_vars()); ?>
 
 <table class="table table-striped table-hover">
     <thead><tr>
        <?php
        foreach($aHead as $cData) {
            print "<th>{$cData}</th>";
        }
        ?>
     </tr></thead>
     <tbody>
        <?php
        foreach($aBody as $aRow) {
            print "<tr>";
            foreach($aRow as $cData){
                print "<td>{$cData}</td>";
            }
            print "</tr>";
        }
        ?>
     
     </tbody>
 </table>
 