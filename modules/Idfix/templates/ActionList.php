<div class="panel panel-default"  style="overflow: hidden;">

  <div class="panel-heading">
    <div class="row">
        <div class="col-sm-6">
           <?php print $ActionListTitle; ?>
           <!-- Panel for javascript ajax messages -->
           <p id="ajaxupdate-error" class="text-danger"></p>

           
           
        </div>
        <div class="col-sm-6">
           <?php print $ActionListPager; ?>
        </div>
    
    </div>  
    
    <?php if($ActionListBreadcrumb): ?>
    <div class="row">
        <div class="col-lg-12">
           <div id="idfix-breadcrumb">
              <?php print $ActionListBreadcrumb; ?>
           </div>
        </div>
    
    </div>
    <?php endif; ?>        
  
  </div>

  <div class="panel-body">
      <div class="row" >
        <div class="col-lg-12">
           <?php print $ActionListMain; ?>
        </div>
      </div>  
  </div> 

</div>



<script type="text/javascript">
function ajaxupdatehandler( idfix_url , value ) {
  console.log(value);
  $.ajax({
    url: idfix_url,
    type: 'POST',
    data: { ajaxupdatevalue: value},
    beforeSend: function(xhr) {
        $('#ajaxupdate-error').hide();
        $('#ajaxupdate-spinner').show();
     }
  }).done( function( msg ){
      if(msg.length) {
        $('#ajaxupdate-error').html(msg);
        $('#ajaxupdate-error').show();
        $('#ajaxupdate-spinner').hide();
      }
      else{
        $('#ajaxupdate-error').hide();
        $('#ajaxupdate-spinner').hide();
     }
  });  
}
</script>


 