<style>
div.date-row div.date-col {padding:0px;}
div.date-row .panel {margin:1px;border:none;}
</style>

<?php foreach( $calendar as $aWeek ): ?>
   <div class="row date-row">
      <?php foreach($aWeek as $aDay){ 
         $iDow = date('w', $aDay['time']);
         $bWeekend = ($iDow == 0 || $iDow == 6);
         $bEnabled = (boolean) $aDay['enabled'];
         $cClass = 'col-sm-' . ($bWeekend?1:2);
         $iDom =  date('D d',$aDay['time']);
         $cData = ($bEnabled? $aDay['data']:'');
         $cPanelClass = ($bEnabled? ($bWeekend?'success':'primary') :'default');
         $cPanelClass = ( date('Ymd') == date('Ymd', $aDay['time']) ? 'danger' : $cPanelClass);

         $cDisplay = "<div class=\"{$cClass} date-col\" ><div class=\"panel panel-{$cPanelClass}\"><div class=\"panel-heading\">{$iDom}</div><div class=\"panel-body\">{$cData}</div></div></div>";
               
                
          print $cDisplay;     
         }
      ?>
   </div>
<?php endforeach; ?>