//výpis všech stanic metra - názvu, souřadnic a linky. Definováno natvrdo proto, aby nemuselo vždy docházet k výpočtu znovu.
function getStations(){
    return [
        {
            station: 'Bořislavka',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.3639325, 50.0985756),
        },
        {
            station: 'Dejvická',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.3927536, 50.1005097),
        },
        {
            station: 'Strašnická',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.4912304305, 50.0726898572),
        },
        {
            station: 'Depo Hostivař',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.5153248015, 50.0753510979),
        },
        {
            station: 'Nemocnice Motol',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.3403820617, 50.0747533766),
        },
        {
            station: 'Můstek',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.4242693948, 50.0832802287),
        },
        {
            station: 'Nádraží Veleslavín',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.3469683364, 50.0957713924),
        },
        {
            station: 'Petřiny',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.3447985617, 50.0875126319),
        },
        {

            station: 'Muzeum',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.4303995688, 50.0797494754),
        },
        {

            station: 'Skalka',
            track: 'A',
            distance: 0,
            coords: new SMap.Coords(14.5077306001, 50.0681038183),
        },
        {

            station: 'Kolbenova',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.515469858, 50.1104433449),
        },
        {

            station: 'Karlovo náměstí',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4188384825, 50.0749308881),
        },
        {

            station: 'Národní třída',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4198451683, 50.0810687639),
        },
        {

            station: 'Rajská zahrada',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.5600722769, 50.106584571),
        },
        {

            station: 'Vysočanská',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.5018981172, 50.1105169368),
        },
        {

            station: 'Lužiny',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.3314746104, 50.044585794),
        },
        {

            station: 'Hůrka',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.3415637677, 50.049721315),
        },
        {

            station: 'Florenc',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4389982336, 50.0905215176),
        },
        {

            station: 'Stodůlky',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.3072394781, 50.0466522277),
        },
        {

            station: 'Smíchovské nádraží',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4091416423, 50.0609212466),
        },
        {

            station: 'Anděl',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4035845164, 50.0704985541),
        },
        {

            station: 'Můstek',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.4242693948, 50.0832802287),
        },
        {

            station: 'Zličín',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.2905667775, 50.0544688397),
        },
        {

            station: 'Palmovka',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.47834300994873, 50.10425567626953),
        },
        {

            station: 'Nové Butovice',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.352347881, 50.0509484986),
        },
        {

            station: 'Luka',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.3217325322, 50.045362273),
        },
        {
            station: 'Hloubětín',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.5375707849, 50.1062870356),
        },
        {
            station: 'Černý most',
            track: 'B',
            distance: 0,
            coords: new SMap.Coords(14.5773662676, 50.1089580939),
        },
        {
            station: 'Vltavská',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.438361071, 50.0989144836),
        },
        {
            station: 'Roztyly',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4779350658, 50.0374295231),
        },
        {
            station: 'Budějovická',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4487581,50.0446586),
        },
        {
            station: 'Florenc',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4389982336, 50.0905215176),
        },
        {
            station: 'I. P. Pavlova',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.430378556, 50.0754034416),
        },
        {
            station: 'Muzeum',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4303995688, 50.0797494754),
        },
        {
            station: 'Pankrác',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4393512335, 50.0513836681),
        },
        {

            station: 'Vyšehrad',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4305302409, 50.0628714141),
        },
        {
            station: 'Nádraží Holešovice',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.44033873, 50.1087440117),
        },
        {
            station: 'Ládví',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4698234762, 50.1265025494),
        },
        {

            station: 'Opatov',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.507982218527872, 50.02772052416168),
        },
        {

            station: 'Letňany',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.5162061404, 50.1252592816),
        },
        {

            station: 'Hlavní nádraží',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4347071593, 50.0841337861),
        },
        {

            station: 'Prosek',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4985142816, 50.1191672775),
        },
        {
            station: 'Háje',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.527738784, 50.0308648944),
        },
        {
            station: 'Kobylisy',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4541150, 50.1241150),
        },
        {
            station: 'Chodov',
            track: 'C',
            coords: new SMap.Coords(14.4917489, 50.0308839),
        },
        {
            station: 'Střížkov',
            track: 'C',
            distance: 0,
            coords: new SMap.Coords(14.4893975152, 50.126239028),
        }
    ]

}

//vypočítáme nejbližší stanici metra ze vstupních souřadnic
function getNearestStation(x, y) {
    var stations = getStations();
    var apartmentCoords = new SMap.Coords(x, y);
    var key;

    for (key in stations) {
        stations[key].distance = stations[key].coords.distance(apartmentCoords);
    }
    //vrátíme nejbližší stanici metra
    return stations.reduce(function(previous, current) {
        return previous.distance < current.distance ? previous : current;
    });
}
