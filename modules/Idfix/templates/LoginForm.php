<?php 

// Get some parameters from the url. Click from email??
$cDefaultEmail = isset($_GET['email']) ? $_GET['email'] : '';
$cDefaultPassword = isset($_GET['password']) ? $_GET['password'] : '';

?>
     <form class="form-signin" role="form" method="post" action="<?php print $cPostUrl; ?>">
        
        <h2 class="form-signin-heading">
        <?php if($bGoodLogin): ?>
            Please sign in
        <?php else:?>
            Please try again
        <?php endif;?>
        </h2>
        
        <div class="form-group">
            <input name="email" type="email" class="form-control" value="<?php print $cDefaultEmail; ?>" placeholder="Email address" required autofocus>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" class="form-control" value="<?php print $cDefaultPassword; ?>" placeholder="Password" required>
        </div>
        
        <?php if(!$bGoodLogin): ?>
          <div class="alert alert-warning">
          No valid email adress and/or password given.
          </div>
        <?php endif;?>
        
        <button class="btn btn-lg btn-primary btn-block" type="submit">
        <?php if($bGoodLogin): ?>
            Sign in
        <?php else:?>
            Try again
        <?php endif;?>
        </button>
    
    </form> 
