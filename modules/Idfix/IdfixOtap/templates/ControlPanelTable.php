<?php foreach($aTable as $aRow): ?>
    <div style="overflow:hidden;" class="alert alert-<?php print $aRow['class']; ?>">
       <strong><?php print $aRow['title']; ?></strong><br />
       <?php print $aRow['info']; ?>
    </div>
<?php endforeach; ?>