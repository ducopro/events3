<div class="col-lg-4 col-sm-6">
    <div class="portfolio-box">
        <img src="<?php print $_image; ?>" class="img-responsive" alt="" />
        <div class="portfolio-box-caption">
            <div class="portfolio-box-caption-content">
                <a href="<?php print $Description; ?>">
                   <?php if( $Icon): ?>
                      <i class="fa fa-4x fa-<?php print $Icon; ?> wow bounceIn"></i>
                   <?php endif; ?>
                   <div class="project-category text-faded">
                       <?php print $Category; ?>
                   </div>
                   <div class="project-name">
                       <?php print $Id; ?>
                   </div>
                   <div class="project-name">
                      <small><?php print $Name; ?></small>
                   </div>
                   <div>
                </a>
                <div class="project-name text-primary">
                  <?php if($_smi_list) print $_smi_list; // Rendered list of social media icons. Use $_smi for the array variant if you need specific theming.?>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>