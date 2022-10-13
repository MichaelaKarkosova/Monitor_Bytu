
{$nextpage = $page+1}
{$previouspage= $page-1}
<nav aria-label="Page navigation example">
    <div class="d-grid gap-2">
        {if $apartments|@count > 9}
            <a class="btn btn-primary btn-lg" href=?{$http}&page={$nextpage}>Další</a>
        {/if}
        {if $page > 1}
        <a class="btn btn-primary btn-lg" href=?{$http}&page={$previouspage}>Předchozí</a>
        {/if}
    </div>
</nav>
