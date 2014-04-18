<div class="panel panel-default">
  
  <div class="panel-heading">
    <h3 class="panel-title"><?php print $icon; ?> <?php print $title; ?></h3>
  </div>
  
  <div class="panel-body">
    <?php print $data; ?>
  </div>
  
  <?php if($description): ?>
  <div class="panel-footer">
    <?php print $description; ?>
  </div>
  <?php endif; ?>

</div>