<?php
  // Branding
  function __GetTopItem($aTop) {
    $bActive = (isset($aTop['active']) and $aTop['active']);
    $bDropDown = (isset($aTop['dropdown']) and is_array($aTop['dropdown']) and count($aTop['dropdown'])>0);
    // Get the class of the LI item
    // special cases for a dropdown and an active element
    $cLiClass = ($bActive) ? 'active' : '';
    $cLiClass .= ($bDropDown) ? ' dropdown' : '';
    // Start with the LI
    $cReturn = "<li class=\"{$cLiClass}\">";
    // Two choices, a normal item or a dropdown
    if($bDropDown) {
      $cReturn .= "<a href=\"{$aTop['href']}#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">{$aTop['icon']}&nbsp;{$aTop['title']} <b class=\"caret\"></b></a>";
      $cReturn .= '<ul class="dropdown-menu">';
      foreach($aTop['dropdown'] as $aDD) {
        if($aDD['type']=='divider') {
           $cReturn .= '<li class="divider"></li>';         
        }
        elseif($aDD['type']=='header') {
            $cReturn .= "<li class=\"dropdown-header\">{$aDD['icon']}&nbsp;{$aDD['title']}</li>";
        }
        else {
            // Is this an active item??
            $cActive = (isset($aDD['active']) and $aDD['active']) ? 'active' : '';
            $cReturn .= "<li class=\"{$cActive}\"><a href=\"{$aDD['href']}\" data-toggle=\"tooltip\" title=\"{$aDD['tooltip']}\">{$aDD['icon']}&nbsp;{$aDD['title']}</a></li>";
        }
      }
      $cReturn .= '</ul>';    
    }
    else{
        $cReturn .= "<a href=\"{$aTop['href']}\" data-toggle=\"tooltip\" title=\"{$aTop['tooltip']}\">{$aTop['icon']}&nbsp;{$aTop['title']}</a>";
    }
    $cReturn .= "</li>";
    return $cReturn;
    
  }
  
?>
<div class="navbar navbar-inverse">
  <div class="navbar-header">
    
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-inverse-collapse">
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    
    <a class="navbar-brand" href="<?php print $navbar['brand']['href']; ?>" data-toggle="tooltip" title="<?php print $navbar['brand']['tooltip']; ?>"><?php print $navbar['brand']['icon']; ?><?php print $navbar['brand']['title']; ?></a>
  
  </div>
  
  <div class="navbar-collapse collapse navbar-inverse-collapse">
    
    <?php 
       // Show our left navigation first
       print '<ul class="nav navbar-nav">';
       foreach( $navbar['left'] as $aTopItem ) {
          print __GetTopItem($aTopItem);
       }
       
       if (isset($navbar['custom'])) {
           foreach($navbar['custom'] as $cHtml) {
            print "<li>$cHtml</li>";
           }
       }

       print '</ul>';
       // Show our right navigation last
       print '<ul class="nav navbar-nav navbar-right">';
       foreach( $navbar['right'] as $aTopItem ) {
          print __GetTopItem($aTopItem);
       }
       print '</ul>';
    
    ?> 
    
    <!--
    <ul class="nav navbar-nav">
       
      
       </ul>
       
       <ul class="nav navbar-nav navbar-right">
       <li><a href="#">Powered by Idfix - Agile Cloud Platform</a></li>
       </ul>
       
      
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