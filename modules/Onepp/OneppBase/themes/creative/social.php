<?php if(count($_smi)>0): ?>
     <ul class="list-inline social">
     <?php foreach($_smi as $smi_icon => $smi_url): ?>
         <li>
             <a href="<?php print $smi_url; ?>"><i class="fa fa-<?php print $smi_icon; ?> fa-fw"></i></a>
         </li>
     <?php endforeach; ?>
     </ul>
<?php endif; ?>