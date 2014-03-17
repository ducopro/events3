
    
    <div class="container" >
     <div class="row">
       <div class="col-sm-8 col-sm-offset-2">        
   
            <div class="jumbotron">        
                <?php print $app; ?>        
            </div>      
            
            <ul class="nav nav-tabs">
              <li class="active"><a href="#login" data-toggle="tab">Login</a></li>
              <li><a href="#resend" data-toggle="tab">Forgot password?</a></li>
              <li><a href="#advanced" data-toggle="tab">Advanced</a></li>
            </ul>
            
            <!-- Tab panes -->
            <div class="tab-content">
              <div class="tab-pane active" id="login">
                 <?php print $form;?>
              </div>
              
              <div class="tab-pane" id="resend">
                 <?php print $password;?>
              </div>
              
              <div class="tab-pane" id="advanced">
                 <?php print $advanced;?>
              </div>
            </div>
            
       </div>
     </div>
    </div>        
