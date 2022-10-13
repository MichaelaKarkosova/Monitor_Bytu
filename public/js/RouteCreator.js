//funkce, co vytváří nejkratší možnou trasu a tím umožňuje výpočet vzdálenosti
function CreateRoute(coords, callback){
    var run = function(route) {
        callback(route.getResults()["length"]);
    }
    new SMap.Route(coords, run,  { criterion: "short" })
}

//tato funkce slouží k dopočítání a naplnění vzdálenosti od metra ve výpisu
function fillRouteInfo(i, name, distance, track){
    //použitá jednotka
    var dist = "m";
    //převod jednotek na km
    if (distance > 1000){
        distance = distance/1000;
        dist = "km";
    }
    //pokud je název ulice a metra stejný - např. Praha- Kobylisy, vrací hodnotu undefined nebo 0. Nastavuju tedy nějakou defaultní hodnotu.
    if (distance === undefined || distance === 0){
        distance = 300;
    }
    //naplnění ve výpisu bytů
    document.getElementById('metro'+i).innerText += " "+name+", ("+track+"): "+distance+" "+dist;
}

