<div class="navbar navbar-inverse">
  <div class="navbar-header">
    
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-inverse-collapse">
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    
    <a class="navbar-brand" href="#" data-toggle="tooltip" title="Default tooltip"><?php print $Idfix->aConfig['title']; ?></a>
  
  </div>
  
  <div class="navbar-collapse collapse navbar-inverse-collapse">
    
    <ul class="nav navbar-nav">
      
      <?php
         foreach( $Idfix->aConfig['tables'] as $cTableName => $aTableConfig) {
            $cTitle = $aTableConfig['title'];
            $cTooltip = $aTableConfig['description'];
            $cUrl = $Idfix->GetUrl('', $cTableName,'',1,'','list');
            $cIcon = $Idfix->GetIconHTML( $aTableConfig['icon']);
            $cActive = ($Idfix->cTableName == $cTableName) ? 'active' : '';
            print "<li class=\"{$cActive}\"><a href=\"{$cUrl}\" data-toggle=\"tooltip\" title=\"{$cTooltip}\">{$cIcon}{$cTitle}</a></li>";
         }
      
       ?>
       </ul>
       
       <ul class="nav navbar-nav navbar-right">
       <li><a href="#">Powered by Idfix - Agile Cloud Platform</a></li>
       </ul>
       
       <!--
      <li class="active"><a href="#">Active</a></li>
      <li><a href="#">Link</a></li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
        <ul class="dropdown-menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li class="divider"></li>
          <li class="dropdown-header">Dropdown header</li>
          <li><a href="#">Separated link</a></li>
          <li><a href="#">One more separated link</a></li>
        </ul>
      </li>
    </ul>
   
    <form class="navbar-form navbar-left">
      <input type="text" class="form-control col-lg-8" placeholder="Search">
    </form>
    
    <ul class="nav navbar-nav navbar-right">
      <li><a href="#">Link</a></li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
        <ul class="dropdown-menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li class="divider"></li>
          <li><a href="#">Separated link</a></li>
        </ul>
      </li>
    </ul>
    -->
  </div>
</div>