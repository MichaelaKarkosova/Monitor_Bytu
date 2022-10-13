<!doctype html>
<html lang="cs">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Byty</title>
		<script type="text/javascript" src="/js/distanceChecker.js"></script>
		<script type="text/javascript" src="/js/RouteCreator.js"></script>
		<script type="text/javascript" src="/js/GetMetroStations.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
		        integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
		        crossorigin="anonymous"></script>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
		      integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">

		<link href="/css/styles.css" rel="stylesheet">
		<script src="/js/jquery-3.6.1.js"></script>
		<script type="text/javascript" src="https://api.mapy.cz/loader.js"></script>
		<script type="text/javascript">Loader.load();</script>
	</head>

		<body {if $colorMode == "dark"}class="dark"{/if}>
        {include file="filters.tpl"}
        {include file="colorswitcher.tpl"}
        {if count($apartments) eq 0}
            {include file="error.tpl"}
        {else}
            {include file="apartments.tpl"}
            {include file="paginator.tpl"}
        {/if}
	</body>
	<script>
	    var i = 0;
	    $(document).ready(function () {
	       	 urls = Object.values(document.getElementsByClassName("addres"));
	        	urls.forEach(loop);
	    });
	    function loop(item, index) {
	        var items = item.innerHTML;
	        window.countDistance(items, i);
	       		 getMapCoordsFromAddress(items, function (result) {
	            var NearestLoc = getNearestStation(result.coords.x, result.coords.y);
	            var coords = [result.coords, NearestLoc["coords"]]; 
	            	CreateRoute(coords, function (result2) {
	                	fillRouteInfo(index, NearestLoc["station"], result2, NearestLoc["track"]);
	            });
	        });
	        i++;
	    }
	</script>
	</body>
<footer class="bg-light text-center text-lg-start">
  <!-- Copyright -->
  <div class="text-center p-3 copyright-bar" style="background-color: rgba(0, 0, 0, 0.2);">
    <p class="copyright-text">Copyright © 2022 by 
    <a class="text-dark copyright-text" href="http://monitorbytu.eu">Monitor Bytů</a></p>
  </div>
  <!-- Copyright -->
</footer>
</html>