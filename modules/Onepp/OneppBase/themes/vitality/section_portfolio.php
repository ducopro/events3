<style>
#<?php print $_identifier; ?> {
   <?php print $_styles; ?>
}
</style>
<section id="<?php print $_identifier; ?>">
  <div class="container text-center wow fadeIn">
      
      <div class="row content-row">
          <div class="col-lg-12">
              <?php print $_header; ?>
              <?php if(count($_cats)>1): ?>
              <div class="portfolio-filter">
                  <ul id="filters" class="clearfix">
                     <li>
                       <span class="filter active" data-filter="<?php print implode(' ',array_keys($_cats));?>">All (#<?php print array_sum($_cats);?>)</span>
                     </li>
                     <?php foreach($_cats as $cCatName => $iCatCount): ?>
                      <li>
                          <span class="filter" data-filter="<?php print $cCatName; ?>"><?php print ucfirst($cCatName) . " (#{$iCatCount})"; ?></span>
                      </li>
                     <?php endforeach; ?>
                  </ul>
              </div>
              <?php endif; ?>
          </div>
      </div>
      
      
      <div class="row">
         <div class="col-lg-12">
            <div id="portfoliolist">
               <?php print $_content; ?>
            </div>
         </div>
      </div>
      
  </div>
</section>   

<?php print $_popups; ?>   