<!DOCTYPE html>
<html>
  <head>
    <title>Idfix - Agile Cloud Development</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">

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
      
      <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
           <?php echo $messages; ?>
        </div>
      </div>
      
      <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
           <?php echo $content; ?>
        </div>
      </div>
    
    </div>    
    
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) http://code.jquery.com/ui/1.10.4/jquery-ui.min.js -->
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
  </body>
</html>