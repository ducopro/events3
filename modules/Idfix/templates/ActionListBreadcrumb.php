<ol class="breadcrumb">

<?php

foreach ($aBreadcrumb as $cTitle => $cHref)
{
    print "<li><a href=\"{$cHref}\">{$cTitle}</a></li>";
}

?>

</ol>