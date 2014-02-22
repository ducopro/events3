<ul class="nav nav-tabs">
  <?php

    foreach ($aButtonbar as $aButton)
    {
      print "<li class=\"{$aButton['class']}\"><a data-toggle=\"tooltip\" title=\"{$aButton['description']}\" href=\"{$aButton['href']}\">{$aButton['title']}</a></li>";  
    }

  ?>
</ul>