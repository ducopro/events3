<div id="idfix-pager">
   <ul class="pagination pull-right">
   
      <li class="active">
         <a href="<?php print $cUrlBackward; ?>">
            <span class="glyphicon glyphicon-backward"></span>
         </a>
      </li>
     
     <li class="disabled">
       <span><?php print $cInfo; ?></span>
     </li>
     
      <li class="active">
         <a href="<?php print $cUrlForward; ?>">
            <span class="glyphicon glyphicon-forward"></span>
         </a>
      </li>
   
     <li class="disabled">
       <span>#&nbsp;<?php print $iRecordsTotal?></span>
     </li>
     
     <li class="active">
       <a href="<?php print $cUrlView; ?>">
            <span class="glyphicon glyphicon-calendar"></span>
         </a>
     </li>
   </ul>
</div>