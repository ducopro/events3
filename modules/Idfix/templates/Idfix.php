<!DOCTYPE html>
<html>
  <head>
    <title>Idfix - Agile Cloud Development</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    
    <!-- Bootstrap -->
    <?php if($theme): ?> 
       <link href="//maxcdn.bootstrapcdn.com/bootswatch/3.2.0/<?php print $theme; ?>/bootstrap.min.css" rel="stylesheet" />
    <?php else: ?>
       <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" />
    <?php endif; ?>
    
    <!-- Font awesome-->
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet" />    
    
    <!-- Jquery -->
    <script src="http://code.jquery.com/jquery.js"></script>
    
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
 
  
  </head>
  <body>
    
    <?php echo $navbar; ?>
    
    <div class="container-fluid">
      
      <div class="row" id="container-row-messages">
        <div class="col-lg-10 col-lg-offset-1">
           <?php echo $messages; ?>
        </div>
      </div>
      
      <div class="row" id="container-row-content">
        <div class="col-lg-10 col-lg-offset-1" id="container-row-content-col">
           <?php echo $content; ?>
        </div>
      </div>
    
    </div>    
    
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) http://code.jquery.com/ui/1.10.4/jquery-ui.min.js -->
    <script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    
    <?php echo $javascript; ?>
     
  </body>
</html>