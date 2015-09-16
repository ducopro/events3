    <nav class="navbar navbar-inverse navbar-fixed-top navbar-expanded">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand page-scroll" href="#page-top">
                <?php if($Icon) print "<i class=\"fa fa-{$Icon} text-primary\"></i>&nbsp;"; ?>
                <?php print $Name; ?>
                </a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <?php if( count($_menu) ): ?>    
                <ul class="nav navbar-nav navbar-right">
                    <?php foreach($_menu as $menu_name => $menu_id ): ?>
                    <li><a class="page-scroll" href="#<?php print $menu_id; ?>"><?php print $menu_name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>
