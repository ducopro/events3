<h2>Choose Configuration</h2>
<p>Multiple configurations and environments are available on this server. Choose the one you wish to login to.</p>
<div class="panel-group" id="accordion">

  <?php foreach($aList as $cConfigName => $aConfig): 
      $cClass = ($aConfig['active'] ? 'in' : '');
      $aEnvironments = $aConfig['env'];
  ?>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#<?php print $cConfigName ?>">
          <?php print $cConfigName ?>
        </a>
      </h4>
    </div>
    <div id="<?php print $cConfigName ?>" class="panel-collapse collapse <?php print $cClass?>">
      <div class="panel-body">
        
        <div class="btn-group">
            <?php foreach( $aEnvironments as $cEnvId => $aEnv):
                $cDisabled = $aEnv['found'] ? '' : 'disabled="disabled"';
                $cBtnClass = $aEnv['active'] ? 'active' :'';
                 ?>
                
             <a href="<?php print $aEnv['url'];?>" class="btn btn-primary <?php print $cBtnClass;?>" <?php print $cDisabled;?> role="button"><?php print $aEnv['title'];?></a>           
            <?php endforeach; ?>
        </div>
        
        
      </div>
    </div>
  </div>
  
  <?php endforeach; ?>

</div>  