
    <div style="flex: 1 1 0;">
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
	{if $colorMode == "dark"}
   <a class="btn btn-primary color" onclick="update('colormode', 'light');" name="light" style="width: 100% !important;">Vypnout tmavý mód</a>
	{else}
   <a class="btn btn-primary color" onclick="update('colormode', 'dark');" name="dark" style="width: 100% !important;">Zapnout tmavý mód</a>
	{/if}
</div>
<script>
function update(a, b) {
console.log(searchParams);
    var searchParams = new URLSearchParams(window.location.search);
    if (b != '')
        searchParams.set(a, b);
    else
        searchParams.delete(a);
    window.location.search = searchParams.toString();
}
</script>