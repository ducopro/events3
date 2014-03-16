
     <form class="form-signin" role="form" method="post" action="<?php print $cPostUrl; ?>">
        
        <h2 class="form-signin-heading">Resend password
        </h2>
        
        <div class="form-group">
            <input name="email" type="email" class="form-control" placeholder="Email address" required autofocus>
            <p class="help-block">A new password will be generated for you and an email will be send to the above address.<br />
            <em>For security reasons no feedback will be given if the email-address is not found in our system.</em></p>
        </div>
    
        
        
        <button class="btn btn-lg btn-primary btn-block" type="submit">Resend</button>
    
    </form> 
