<div class="well">
    <form enctype="multipart/form-data" role="form" method="post" action="<?php print $cPostUrl; ?>">
        <div class="panel-group" id="accordion">
            <?php print $cInput; ?>
        </div>
        <br />
        <div class="form-group">
            <?php print $cHidden; ?>
        </div>
    </form>
</div>