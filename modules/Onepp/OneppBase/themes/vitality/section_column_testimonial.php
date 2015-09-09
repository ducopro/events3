<div class="item">
    <div class="row">
        <div class="col-lg-12">
            <?php if( $Icon): ?>
                <i class="fa fa-4x fa-<?php print $Icon; ?>"></i>
            <?php endif; ?>
            <?php if($Id): ?>
               <p class="lead"><?php print $Id; ?></p>
               <hr class="colored" />
            <?php endif; ?>
            <?php if( strip_tags($Text_1)): ?>
               <p class="quote"><?php print $Text_1; ?></p>
            <?php endif; ?>
            <div class="testimonial-info">
                <?php if($_image): ?>
                   <div class="testimonial-img">
                       <img src="<?php print $_image; ?>" class="img-circle img-responsive" alt="" />
                   </div>
                <?php endif; ?>
                <?php if($Name): ?>
                   <div class="testimonial-author">
                       <span class="name"><?php print $Name; ?></span>
                       <!-- <p class="small">CEO of GeneriCorp</p> -->
                        <?php if(count($_smi)>0): ?>
                           <div class="stars">
                              <ul class="list-inline social">
                              <?php foreach($_smi as $smi_icon => $smi_url): ?>
                                 <li>
                                    <a href="<?php print $smi_url; ?>"><i class="fa fa-<?php print $smi_icon; ?> fa-fw"></i></a>
                                 </li>
                              <?php endforeach; ?>
                              </ul>
                           </div>
                        <?php endif; ?>
                   </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>