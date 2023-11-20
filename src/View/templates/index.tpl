<!doctype html>
<html lang="cs">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Monitor bytů</title>
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
            <div class="d-flex justify-content-between myFlex">
                        {include file="colorswitcher.tpl"}
 

    <div class="title text-center">

                    <a href="/" class="title text-center">Monitor bytů</a></div>
    <div class="d-flex fixed-filters align-items-stretch flex-shrink-0 bg-white" style="flex: 1 1 0;padding: 0;">
    <div class="list-group scrollarea" style="position: relative;">
           
            <button class="btn btn-primary list-group-item list-group-item-action active py-2 lh-sm collapsed"
                    data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-current="true"
                    aria-expanded="false"
                    style="text-align: center;"
            >
            <strong class="mb-1 text-center" align="center">Filtry</strong>
            </button>
            <div class="collapse" id="collapseFilters" style="position: absolute; background: #fff; top: 50px;">
              
                      {include file="filters.tpl"}

        </div>
    </div>
</div>

<H2 class="text-center main-header">Celkem v databázi: {$sum}</H2>
                <div class="count-filtered text-center">Celkem podle filtrů: {$count}</div>
                <br>
                            <div class="active-filters text-center"> Použité filtry: {rawurldecode($totalfilters)} </div><br>

        {if count($apartments) eq 0}
            {include file="error.tpl"}
        {else}
            {include file="apartments.tpl"}
            {include file="paginator.tpl"}
        {/if}

<footer class="bg-light text-center text-lg-start">
  <!-- Copyright -->
  <div class="text-center p-3 copyright-bar" style="background-color: rgba(0, 0, 0, 0.2);">
    <p class="copyright-text">Copyright © 2023 by 
    <a class="text-dark copyright-text" href="http://monitorbytu.eu">Monitor Bytů</a></p>
  </div>
  <!-- Copyright -->
</footer>
    </body>
</html>