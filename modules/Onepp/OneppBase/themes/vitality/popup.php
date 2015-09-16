<?php if($_image): ?>
<!-- Start Modal Popup for <?php print $Id; ?> -->
<div class="portfolio-modal modal fade" id="<?php print $_popup_id;?>" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="true">
  <div class="modal-content">
      <div class="close-modal" data-dismiss="modal">
          <div class="lr">
              <div class="rl">
              </div>
          </div>
      </div>
      <div class="modal-body">
          <div class="container">
              <div class="row">
                  <div class="col-lg-8 col-lg-offset-2">
                      <h2><?php print $Id; ?></h2>
                      <hr class="colored" />
                      <p><?php print $Text_1; ?></p>
                  </div>
                  <div class="col-lg-12">
                      <img src="<?php print $_image; ?>" class="img-responsive img-centered" alt="">
                  </div>
                  <div class="col-lg-8 col-lg-offset-2">
                      <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                  </div>
                  
                  <?php if(count($_smi)>0): ?>
                  <div class="col-lg-8 col-lg-offset-2">
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
          </div>
      </div>
  </div>
</div>
<!-- Stop Modal Popup for <?php print $Id; ?> -->
<?php endif; ?>
