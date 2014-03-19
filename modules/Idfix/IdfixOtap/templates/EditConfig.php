<div class="-container-fluid" id="editor-container">
  <div class="row">
    
    <!-- Left Column -->
    <div class="col-sm-12">

           <div class="well">
           <div id="editor"><?php print $cFileContent; ?></div>
           </div>
     
    </div>

  
  </div>
</div>

<style type="text/css" media="screen">
    #editor { 
        min-height:800px;
    }
</style>

<script type="text/javascript">
// Add handler for saving
    $(document).ready( function() {
       
       $("#save-config").click(function(){
          config = editor.getSession().getValue();
          $.post( "<?php print $cUrl?>", { config: config } , function(data){
            DirtyMarker(false);
          });   
       });
       
       height = $(window).height() - $('#editor').offset().top - 20;
       $('#editor').height( height );
    });
    
    function DirtyMarker( isdirty ){
      if(isdirty) {
        console.log('Is Dirty');
        $('#save-config').addClass('btn-danger');
      }    
      else {
        console.log('Is Clean');
        $('#save-config').removeClass('btn-danger');
      }
    }
    
</script>

<script src="http://cdnjs.cloudflare.com/ajax/libs/ace/1.1.01/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/ace/1.1.01/theme-xcode.js" type="text/javascript" charset="utf-8"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/ace/1.1.01/mode-yaml.js" type="text/javascript" charset="utf-8"></script>

<!--

<script src="//cdn.jsdelivr.net/ace/1.1.3/mode-javascript.js"></script>
<script src="//cdn.jsdelivr.net/ace/1.1.3/theme-monokai.js"></script>
-->
<script type="text/javascript">
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/xcode");
    editor.getSession().setMode("ace/mode/yaml");
    editor.getSession().setUseWrapMode(false);
    editor.getSession().setFoldStyle("markbegin");
    
    editor.getSession().on("change", function(e) {
     DirtyMarker(true);
    });
    
</script>

