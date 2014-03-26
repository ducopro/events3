<table class="table  table-striped table-hover table-condensed table-bordered">

    <thead>
        <th>Description</th>
        <th>Data</th>
    </thead>
    
    <tbody>
        <tr>
            <td>#Records: </td>
            <td><?php print $aRow['count']?></td>
        </tr>
        <tr>
            <td>#Unique parents: </td>
            <td><?php print $aRow['parents']?></td>
        </tr>
        <tr>
            <td>#Average by parent: </td>
            <td><?php print $aRow['count']/$aRow['parents']?></td>
        </tr>
        <tr>
            <td>#Records created by unique users: </td>
            <td><?php print $aRow['creators']?></td>
        </tr>
        <tr>
            <td>First record created on: </td>
            <td><?php print date('l d-m-Y (H:i)',$aRow['ts_first'])?></td>
        </tr>
        <tr>
            <td>Last record created on: </td>
            <td><?php print date('l d-m-Y (H:i)',$aRow['ts_last'])?></td>
        </tr>
        <tr>
            <td>Last change on: </td>
            <td><?php print date('l d-m-Y (H:i)',$aRow['ts_change'])?></td>
        </tr>
    </tbody>
</table>