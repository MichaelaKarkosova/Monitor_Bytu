
	<form name="colors" id="colors">
	{if $colorMode == "dark"}
		<a class="btn btn-primary color" onclick="update('colormode', 'light');" name="light">Vypnout tmavý mód</a>
	{else}
		<a class="btn btn-primary color" onclick="update('colormode', 'dark');" name="dark">Zapnout tmavý mód</a>
	{/if}
	</form>


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