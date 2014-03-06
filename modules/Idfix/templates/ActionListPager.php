<?php

/*
$aPager['iRecordsTotal'] = $iRecordsTotal;
$aPager['iRecordsPage'] = $iRecordsByPage;
$aPager['iPageTotal'] = $iPages;
$aPager['iPageCurrent'] = $iPageCurrent;
$aPager['iPageNext'] = $iPageNext;
$aPager['iPagePrev'] = $iPagePrevious;
$aPager['iSetStart'] = $iStartSet;
$aPager['iSetStop'] = $iStopSet;
*/

if (!function_exists('_ActionListPagerRenderLi')) {
    function _ActionListPagerRenderLi($iPageCurrent, $iPage, $cIcon = '')
    {
        $ev3 = Events3::GetHandler();
        $oIdfix = $ev3->LoadModule('Idfix');
        $cClass = '';
        $cHref = $oIdfix->GetUrl('', '', '', $iPage, null, 'list');
        // Check the current page
        if($iPage == $iPageCurrent) {
           $cClass = 'active disabled';
           $cHref = '#';
        }
        
        $cRetval = "<li class=\"{$cClass}\"><a href=\"{$cHref}\">";
        if ($cIcon)
        {
            $cRetval .= "<span class=\"glyphicon glyphicon-{$cIcon}\"></span>&nbsp;<span>$iPage</span>";
        } else
        {
            $cRetval .= "<span>$iPage</span>";
        }
        $cRetval .= "</a></li>";
        
        return $cRetval;
    }
}
?>

<div id="idfix-pager">
<ul class="pagination pull-right" style="--margin-top: 0px;">
  <?php
    print _ActionListPagerRenderLi($iPageCurrent,1,'step-backward');
    print _ActionListPagerRenderLi($iPageCurrent,$iPagePrev,'backward');
    for($i =$iSetStart; $i <= $iSetStop; $i++) {
       print _ActionListPagerRenderLi($iPageCurrent, $i);    
    }
    print _ActionListPagerRenderLi($iPageCurrent, $iPageNext,'forward');
    print _ActionListPagerRenderLi($iPageCurrent, $iPageTotal,'step-forward');
  ?>
  <li class="disabled">
    <span>#&nbsp;<?php print $iRecordsTotal?></span>
  </li>
</ul>

</div>