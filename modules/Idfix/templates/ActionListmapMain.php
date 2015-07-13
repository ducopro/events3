<style type="text/css">
  html { height: 100% }
  body { height: 100%; margin: 0; padding: 0 }
  div.container-fluid {height:100%;}
  #container-row-content {height:100%;}
  #container-row-content-col {height:100%;}
  #container-row-content-col div.panel {height:90%;}
  #container-row-content-col div.panel div.panel-body {height:100%;}
  #container-row-content-col div.panel div.panel-body div.row {height:100%;}
  #container-row-content-col div.panel div.panel-body div.row > div {height:100%;}
  #map-canvas { height: 100% }
</style>

<div id="map-canvas"></div>

<script type="text/javascript"
   src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCiKGeNVT_2yiQ_q2XqTx_RAIsLWniHZDs">
</script>
<script type="text/javascript">
   function initialize() {
     var map = new google.maps.Map(document.getElementById('map-canvas'));
     setMarkers(map, idfix_data);    
   }
   
   var idfix_data = [
   <?php echo $js ?>
   ];
   
   function setMarkers(map, locations) {
     var latlngbounds = new google.maps.LatLngBounds();
     for (var i = 0; i < locations.length; i++) {
       var place = locations[i];
       var myLatLng = new google.maps.LatLng(place[1], place[2]);
       latlngbounds.extend(myLatLng);
       var marker = new google.maps.Marker({
           position: myLatLng,
           map: map,
           title: place[0],
           idfix_id: place[3]
       });
       google.maps.event.addListener(marker, 'click', function() {
          //map.setZoom(12);
          //map.setCenter(this.getPosition());
          $('#map-item-' + this.idfix_id).modal('show');
          //alert(this.idfix_id);
       });
     }
     map.fitBounds(latlngbounds);
   }

   google.maps.event.addDomListener(window, 'load', initialize);
</script>

<?php echo $popup ?>