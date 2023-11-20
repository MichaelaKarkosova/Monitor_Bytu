<?php

namespace App;

use App\Database;
use App\Template\ColorMode;
use App\Template\TemplateFactoryInterface;
use App\View\IndexParameters;
use App\View\JobsTemplateParameters;


//v této třídě se předávají data do smarty šablon.
class DataRenderer {
    private Database $db;
    private TemplateFactoryInterface $templateFactory;
    private ColorMode $colorMode;

    public function __construct(Database $db, TemplateFactoryInterface $templateFactory, ColorMode $colorMode){
        $this->db = $db;
        $this->templateFactory = $templateFactory;
        $this->colorMode = $colorMode;
    }
    //zde budeme získávat souhrn dat z databáze a dávat je do filtrů. Můžeme tak filtrovat podle reálných dat z databáze
    public function getDataFromDb(string $column, ?string $isDetail) : array {
        $db_c = $this->db->getConnection();
        $result = "";
        //do této metody lze poslat isDetail 1 nebo 0. Pokud je 1, budeme brát data z detailů. Pokud ne, z bytů.
        if ($isDetail ){
            // u detailu je nutný udělat join na byty_detaily
            $sql = "SELECT bd.".$column.", COUNT(bd.id) AS count FROM byty_detaily bd GROUP BY bd.".$column." ORDER BY bd.".$column." ASC";}
        else {
            $sql = "SELECT ".$column.", COUNT(id) AS count FROM byty GROUP BY ".$column." ORDER BY ".$column." ASC";
        }
        $stmt = $db_c->prepare($sql);
        $stmt->execute();
        //vezmeme výsledky sql
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        //a vrátíme je
        return $result;

    }

    public function getActiveFiltersForSmarty(){
        $i = 0;
    
        $done = false;
        $activefilters = '';
        [$conditions, $binds, $params] = $this->getActiveFilters();
        foreach ($conditions as $c) {
            $indexAddition = 1;

            if ($c === "pricetotal >= ?"){
                $activefilters = $activefilters."od ".$params[$i]." kč";
            }
            if ($c === "pricetotal <= ?"){
                $activefilters = $activefilters.", do ".$params[$i]." kč";
            }
            if ($c === "vymera >= ?"){
                $activefilters = $activefilters.", od ".$params[$i]." m2";
            }
            if ($c === "vymera <= ?"){
                $activefilters = $activefilters.", do ".$params[$i]." m2";
             }
    
            if (false !== strpos($c, 'vytah IS NULL)')) {
                $activefilters = $activefilters.", vytah: nezáleží";
               }
            if (false !== strpos($c, 'vytah = 0)')) {
                $activefilters = $activefilters.", bez výtahu";
               }
            if (false !== strpos($c, 'vytah = 1)')) {
                $activefilters = $activefilters.", s výtahem";
               }
            if (false !== strpos($c, 'balkon IS NULL)')) {
                $activefilters = $activefilters.", balkon: nezáleží";
               }
            if (false !== strpos($c, 'balkon = 0)')) {
                $activefilters = $activefilters.", bez balkonu";
               }
            if (false !== strpos($c, 'balkon = 1)')) {
                $activefilters = $activefilters.", s balkonem";
               }
            $toadd = "";
            if (false !== strpos($c, 'patro IN')) {
              $toadd = ". patro";
              $activefilters = $activefilters.$toadd;
           }       
           else if (false !== strpos($c, 'IN')){
                $indexAddition = substr_count($c, '?'); 
                 $activefilters .= ', ' . implode(', ', array_slice($params, $i, $indexAddition)).$toadd; 
                 $done = true;
            }
    
        $i += $indexAddition;
    }
    $activefilters = preg_replace("/\, \d+,/", ",", $activefilters);
    
    return $activefilters;
}

    public function getAvailableSources(){
        $db = $this->db->getConnection();
        $sources = ['bezrealitky', 'sreality', 'realitymix'];
        $allsources = [];
        foreach ($sources as $s){
            $sql = 'select count(*) from byty_detaily where byty_id like "%'.$s.'%"';
             $stmt = $db->prepare($sql);
            $stmt->execute();
        //a fetchneme všechny výsledky
            $resultt = $stmt->get_result();
            $count = $resultt->fetch_row();
            $result = array(['count' => $count[0], 'zdroj' => $s]);
            $allsources = array_merge($allsources, $result);
        }

        return $allsources;
    }

    public function LoadDataForSmarty() : void {
        $db_c = $this->db->getConnection();
        //filtry se nachází v getu
        $filters = $_GET;
        // v getu se nachází i order by a stránka, což nepatří k filtrům. Odstraníme je tedy.
        unset($filters["page"]);
         $http = http_build_query($filters, "", "&", PHP_QUERY_RFC3986);
        unset($filters["order"]);
        //získáme informaci, na jaké stránce se nacházíme.
        $page = $this->getCurrentPage();
        //sestavíme url ve formátu RFC3986. Takhle se vyhneme diakritice.
        //vytvoříme object IndexParmeters - parametry pro šablonu index.tpl;
        $countall = $this->getTotalResultsFromFilter();
        $templateParams = new IndexParameters();
        //řekneme objektu, že filtry jsou $filters
        $templateParams->filters = $filters;
        $templateParams->totalfilters = $this->getActiveFiltersForSmarty();
        //vytáhneme z výpisu detailů bytů informaci o patrech a předáme ji do templatu jako parametr "stairs"
        $templateParams->stairs = $this->getDataFromDb("patro", 1);
        //vytáhneme z výpisu bytů informaci o částech a předáme ji do templatu jako parametr "parts"
        $templateParams->parts = $this->getDataFromDb("part", 0);
        //vytáhneme z výpisu bytů informaci o stavu bytů a předáme ji do templatu jako parametr "stav"
        $templateParams->conditions = $this->getDataFromDb("stav", 1);
        //vytáhneme z výpisu bytů informaci o stavu bytů a předáme ji do templatu jako parametr "stav"
        $templateParams->sizes = $this->getDataFromDb("dispozice", 1);
        //vytáhneme z výpisu bytů informaci o tom, zda v bytu mohou být zvířata a předáme ji do templatu jako parametr "animals"
        $templateParams->source = $this->getAvailableSources();
        $templateParams->furniture = $this->getDataFromDb("vybaveni", 1);
        $templateParams->animals = $this->getDataFromDb("zvirata", 1);
        $templateParams->imported = $this->getDataFromDb("DATE_SUB(imported, INTERVAL 1 HOUR)", 0);
        $templateParams->first = $this->getDataFromDb("first", 0);

        //vytáhneme z výpisu bytů informaci o přítomnosti balkonu a předáme ji do templatu jako parametr "balcony"
        $templateParams->balcony = $this->getDataFromDb("balkon", 1);
        $templateParams->count= $countall;
        //vytáhneme z výpisu bytů informaci o přítomnosti výtahu a předáme ji do templatu jako parametr "elevator"
        $templateParams->elevator = $this->getDataFromDb("vytah", 1);
        //vybereme počet detailů a předáme ho do templatu jako parametr "sum"
        $templateParams->sum = $db_c->query("SELECT count('id') from byty_detaily as count")->fetch_row()[0];
        //zjistíme, které řazení je aktivní a předáem ho do templatu jako parametr "order"
        $templateParams->order = $_GET["order"] ?? "cheap";
        //vytáhneme si filtrované byty a předáme je šabloně jako parametr "apartments"
        $templateParams->apartments = $this->getFilteredData();
        $templateParams->colorMode = $this->getColorMode();
  //      $templateParams->average = "$this->getAverageByType($a, $b)";
        //předáme do templatu parametr stránky
        $templateParams->page = $page;
        $templateParams->http = $http;
        //vytvoříme šablonu a vyrenderujeme ji
        $this->templateFactory->create('index.tpl', $templateParams)->render();
        $apartmentsparams = new ApartmentsTemplateParameters();
        $apartmentsparams->count = $countall;
        $this->templateFactory->create('apartments.tpl', $apartmentsparams)->render();
    }

    public function getColorMode() : string{
        if ($_GET["colormode"]){
            $this->colorMode->setColorMode($_GET["colormode"]);
            return $_GET["colormode"];
        }
        else{
            return $this->colorMode->getColorMode();
        }

    }
    public function getFilteredData($limited = 0) : array {
        //první bind musí být vždy i -  jako int.
        $binds[] = "i";
        //připojíme se k DB
        $db = $this->db->getConnection();
        //zjistíme aktuální limit z filtru
        $limit = $this->getCurrentLimit();
        //zjistíme aktivní řazení
        $order_s = $this->getOrder();
        //z aktivních filtrů zjistíme parametry, bindy (datové typy) a podmínky. Např. price < 20000 - zde je price parametr a < 20000 podmínka.
        [$conditions, $binds, $params] = $this->getActiveFilters();
        //pokud jsou podmínky nastavené - ve filtru tedy něco je.
        if ($conditions != NULL) {
            //dekodujeme filtry zpátky, aby obsahovaly i diakritiku a mohli jsme s nimi pracovat v databázi
            $params = $this->decodeFilters($params);

            //nastavíme where podmínku pomocí všech podmínek
            $where = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));
            $sql = "select b.id, b.longpart, b.part, bd.balkon, b.pricetotal, b.part, bd.dispozice, bd.vymera, bd.zvirata, bd.patro, bd.vybaveni, bd.vytah, bd.stav, b.price, b.url, b.name, b.first, b.imported from byty b join byty_detaily bd on bd.byty_id=b.id " . $where . " order by " . $order_s . $limit;
            }
         else {
             $sql = "select b.id, b.part, b.longpart, b.pricetotal, bd.balkon, b.part, bd.dispozice, bd.vymera, bd.zvirata, bd.patro, bd.vybaveni, bd.vytah, bd.stav, b.price, b.url, b.name, b.first, b.imported from byty b join byty_detaily bd on bd.byty_id=b.id order by pricetotal". $limit;
         }
         //pro jistotu znova zkontrolujeme, zda jsou filtry aktivní
        $stmt = $db->prepare($sql);
        if ($conditions != NULL) {
            //nabindujeme data do statementu
            $stmt->bind_param(implode('', $binds), ...array_values($params));
            //druhý statement bez limitu
        }
        //vykonáme sql query
        $stmt->execute();
        //a fetchneme všechny výsledky
        $result = $stmt->get_result();
        $apartments = $result->fetch_all(MYSQLI_ASSOC);
        for ($i = 0; $i < count($apartments); $i++){
            $apartments[$i]["average"] = $this->getAverageByType( $apartments[$i]["part"],  $apartments[$i]["stav"]);
        }
    
       // print_r($apartments[1]);
        return $apartments ?? [];
    }

    public function getTotalResultsFromFilter(){
        //první bind musí být vždy i -  jako int.
        $binds[] = "i";
        //připojíme se k DB
        $db = $this->db->getConnection();
        //zjistíme aktuální limit z filtru
        $limit = $this->getCurrentLimit();
        //zjistíme aktivní řazení
        $order_s = $this->getOrder();
        //z aktivních filtrů zjistíme parametry, bindy (datové typy) a podmínky. Např. price < 20000 - zde je price parametr a < 20000 podmínka.
        [$conditions, $binds, $params] = $this->getActiveFilters();
        //pokud jsou podmínky nastavené - ve filtru tedy něco je.
        if ($conditions != NULL) {
            //dekodujeme filtry zpátky, aby obsahovaly i diakritiku a mohli jsme s nimi pracovat v databázi
            $params = $this->decodeFilters($params);
            //nastavíme where podmínku pomocí všech podmínek
            $where = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));
            $sql = "select b.id, b.longpart, b.part, bd.balkon, b.pricetotal, b.part, bd.dispozice, bd.vymera, bd.zvirata, bd.patro, bd.vybaveni, bd.vytah, bd.stav, b.price, b.url, b.name, b.first, b.imported from byty b join byty_detaily bd on bd.byty_id=b.id " . $where . " order by " . $order_s;
            }
         else {
             $sql = "select b.id, b.part, b.longpart, b.pricetotal, bd.balkon, b.part, bd.dispozice, bd.vymera, bd.zvirata, bd.patro, bd.vybaveni, bd.vytah, bd.stav, b.price, b.url, b.name, b.first, b.imported from byty b join byty_detaily bd on bd.byty_id=b.id order by pricetotal";
         }
         //pro jistotu znova zkontrolujeme, zda jsou filtry aktivní
        $stmt = $db->prepare($sql);
        if ($conditions != NULL) {
            //nabindujeme data do statementu
            $stmt->bind_param(implode('', $binds), ...array_values($params));
            //druhý statement bez limitu
        }
        //vykonáme sql query
        $stmt->execute();
        //a fetchneme všechny výsledky
        $result = $stmt->get_result();
        $apartments = $result->fetch_all(MYSQLI_ASSOC);
        //a vrátíme výsledky nebo prázdné pole
        return count($apartments) ?? 0;
    }

    public function getOrder() : string {
        //pokud order není nastaven, je defaultní cheap. Jinak použijeme informaci z GETU
        $order = $_GET["order"] ?? "cheap";
        //nastavíme podmínku do databáze podle typu řazení
        //od nejlevnějšího, nejmenšího, největšího, městské části abecedně, nejlevnějšího
        switch ($order){
            case "expensive":
                $order_s = 'pricetotal desc';
                break;
            case "areamin":
                $order_s = 'vymera asc';
                break;
            case "areamax":
                $order_s = 'vymera desc';
                break;
            case "part":
                $order_s = 'part';
                break;
            case "cheap":
                $order_s = 'pricetotal';
                break;
            case "new":
                $order_s = 'imported desc';
                break;
            case "old":
                $order_s = 'imported asc';
                break;
            case "firstnew":
                $order_s = 'first desc';
                break;
            case "firstlast":
                $order_s = 'first asc';
                break;
            default:
                $order_s = 'pricetotal';
        }
        return $order_s;
    }

    public function getAverageByType($part, $stav){
        $conn = $this->db->getConnection();
        $sql = "select averageprice from byty.average where part = ? and stav = ?";
        $statement = $conn->prepare($sql);
        $statement->bind_param("ss", $part, $stav);
        $statement->execute();
        $res = $statement->get_result()->fetch_all(MYSQLI_ASSOC);  
        return $res[0]["averageprice"];
    }
    //tato metoda vrací aktivní filtry
    public function getActiveFilters() : array {
        //filtry získáme z getu
        $filters = $_GET;
        //pokud je ve filtru stránka, odstraníme ji
        if (isset($filters['page'])) {
            unset($filters["page"]);
        }
        //odstranímee order z filtru
           unset($filters["order"]);

        $params = [];
        $binds = [];
        $conditions = [];
        if (!isset($filters['pricemin'])) {
           $filters['pricemin'] = 500;
        }
        if (!isset($filters['pricemax'])) {
           $filters['pricemax'] = 200000;
        }
        //nastavíme podmínky do databáze pro filtry, pokud jsou nastaveny.
        if (isset($filters['pricemin'])) {
            $conditions[] = 'pricetotal >= ?';
            $params[] = $filters['pricemin'];
            $binds[] = "i";
        }
        if (isset($filters['areamin'])) {
            $conditions[] = 'vymera >= ?';
            $params[] = rawurldecode($filters['areamin']);
            $binds[] = "i";
        }

        if (isset($filters['areamax'])) {
            $conditions[] = 'vymera <= ?';
            $params[] = $filters['areamax'];
            $binds[] = "i";
        }
        if (isset($filters['pricemax'])) {
            $conditions[] = 'pricetotal <= ?';
            $params[] = $filters['pricemax'];
            $binds[] = "i";
        }
        //v následujících filtrech mohou být pole. Proto použijeme do databáze IN (x, y, z)
        if (isset($filters['part']) && !empty($filters['part'])) {
            $innerValue = $filters['part'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'part = ""';
            }

            if (count($innerValue)) {
                $innerConditions[] = 'part IN (' . implode(', ', array_fill(0, count($innerValue), '?')) . ')';
                $params = array_merge($params, $innerValue);
                $binds = array_merge($binds, array_fill(0, count($innerValue), 's'));
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
        if (isset($filters['size']) && !empty($filters['size'])) {
            $innerValue = $filters['size'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'dispozice IS NULL';
            }

            if (count($innerValue)) {
                $innerConditions[] = 'dispozice IN (' . implode(', ', array_fill(0, count($innerValue), '?')) . ')';
                $params = array_merge($params, $innerValue);
                $binds = array_merge($binds, array_fill(0, count($innerValue), 's'));
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
    
        if (isset($filters['source'])) {
                $conditions[] = 'INSTR(url, ?)';
                $params[] = $filters['source'];
                $binds[] = "s";
        }
        if (isset($filters['stav']) && !empty($filters['stav'])) {
            $innerValue = $filters['stav'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'stav IS NULL';
            }

            if (count($innerValue)) {
                $innerConditions[] = 'stav IN (' . implode(', ', array_fill(0, count($innerValue), '?')) . ')';
                $params = array_merge($params, $innerValue);
                $binds = array_merge($binds, array_fill(0, count($innerValue), 's'));
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
        if (isset($filters['stairs']) && !empty($filters['stairs'])) {
            $innerValue = $filters['stairs'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'patro IS NULL';
            }

            if (count($innerValue)) {
                $innerConditions[] = 'patro IN (' . implode(', ', array_fill(0, count($innerValue), '?')) . ')';
                $params = array_merge($params, $innerValue);
                $binds = array_merge($binds, array_fill(0, count($innerValue), 's'));
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
        if (isset($filters['elevator']) && !empty($filters['elevator'])) {
            $innerValue = $filters['elevator'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'vytah IS NULL';
            }
            if (FALSE !== ($nullIndex = array_search("Ano", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'vytah = 1';
            }
                        if (FALSE !== ($nullIndex = array_search("Ne", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'vytah = 0 ';
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
        if (isset($filters['balcony']) && !empty($filters['balcony'])) {
            $innerValue = $filters['balcony'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'balkon IS NULL';
            }


            if (FALSE !== ($nullIndex = array_search("Ano", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'balkon = 1';
            }

            if (FALSE !== ($nullIndex = array_search("Ne", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'balkon = 0';
            }


            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
  
        if (isset($filters['animals']) && !empty($filters['animals'])) {
            $innerValue = $filters['animals'];
            $innerConditions = [];

            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'zvirata IS NULL';
            }


            if (FALSE !== ($nullIndex = array_search("Ano", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'zvirata = 1';
            }

            if (FALSE !== ($nullIndex = array_search("Ne", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'zvirata = 0';
            }


            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }
        if (isset($filters['furniture']) && !empty($filters['furniture'])) {
            $innerValue = $filters['furniture'];
            $innerConditions = [];
            if (FALSE !== ($nullIndex = array_search("Neuvedeno", $innerValue, TRUE))) {
                unset($innerValue[$nullIndex]);
                $innerConditions[] = 'vybaveni IS NULL';
            }

            if (count($innerValue)) {
                $innerConditions[] = 'vybaveni IN (' . implode(', ', array_fill(0, count($innerValue), '?')) . ')';
                $params = array_merge($params, $innerValue);
                $binds = array_merge($binds, array_fill(0, count($innerValue), 's'));
            }

            if (count($innerConditions)) {
                $conditions[] = '(' . implode(' OR ', $innerConditions) . ')';
            }
        }

        return [$conditions, $binds, $params];

    }

    //vracíme aktuální limit pro databázi.
    public function getCurrentLimit() : string {
        //zjistíme, na jaké jsme stránce
        if (isset($_GET["page"])) {
            $page = $_GET["page"];
            $numlimit = ($page - 1) * 10;
        }
        else {
            $page = 1;
            $numlimit = $page-1;
        }
        //a sestavíme limit pro sql příkaz
        $limit = " limit " . $numlimit . ", 10";
        //a vrátíme ho
        return $limit;
    }

    //vracíme aktuální stránku
    public  function getCurrentPage() {
        if (isset($_GET["page"])) {
            $page = $_GET["page"];
        } else {
            $page = 1;
        }
        return $page;
    }

    //Rekurzivní metoda.
    //dekodujeme filtry. Přichází sem pole parametrů bez diakritiky, resp. nahrazenou diakritikou.
    //Poté vracíme dekodované filtry tak, aby s nimi mohla pracovat databáze.
   public function decodeFilters(array $filters): array {
        foreach ($filters as $k => $v) {
            if (is_array($v)) {
                $v = $this->decodeFilters($v);
            }

            if (is_string($v)) {
                $v = rawurldecode($v);
            }

            $filters[$k] = $v;
        }
        return $filters;
    }
}