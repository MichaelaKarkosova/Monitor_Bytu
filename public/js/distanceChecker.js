

//převod adresy na souřadnice a získáme return hodnotu v callbacku
function getMapCoordsFromAddress(address, callback) {
    new SMap.Geocoder(address, function (geocoder) {
        if (!geocoder.getResults()[0].results.length) {
            console.warn("Unknown address.");
            return;
        }

        callback(geocoder.getResults()[0].results[0]);
    });

}

function countDistance(address, i) {
    new SMap.Geocoder(address, function (geocoder) {
        //alert při chybě adresy
        if (!geocoder.getResults()[0].results.length) {
            console.warn("Unknown address.");
            return;
        }
        //nastavujeme vysledky na souřadnice prvního výsledku
        var vysledky = geocoder.getResults()[0].results[0];
        var x = vysledky.coords.x;
        var y = vysledky.coords.y;
        var souradnice = new SMap.Coords(x, y);
        //definujeme centrum Prahy - v tomto případě se jedná o ulici Ječná
        var centrum = new SMap.Coords(14.423678752306454, 50.07545077085763);
        //vypočítáme vzdálenosti vstupního parametru - např. adresy bytu od centra města v km
        distance = (souradnice.distance(centrum) / 1000).toFixed(1);
        //přepíšeme třídu distance ve smarty na tuto hzodnotu
        document.getElementById('distance'+i).innerText += " "+distance+" km";
    });
}

