<?php
declare(strict_types=1);

namespace App\Read;
date_default_timezone_set("Europe/Prague");
use App\database;
use App\ValueObject\Apartment;
use App\ValueObject\Apartment_detailed;
use App\ValueObject\ApartmentsResult;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;


//v této třídě čteme data z webu bezrealitky
class BezRealitkyReader implements ChainableReaderInterface {

    private database $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    public function canRead(string $source): bool {
        $needle = 'bezrealitky';
        //kontrola, zda URL obsahuje bezrealitky. Zároven se zde rozhoduje chain.
        return strpos($source, $needle) > 0;

    }

    public function read(string $source): ApartmentsResult {
        $i = 1;
        $finalapartments = [];
        $ok = true;
        //pokud stránka není v url, resp. v geetu není uvedena, automaticky ji bereme jako první a dodáváme tuto informaci do odkazu
        if (!strpos($source, "&page")){
            $source .= "&page=1";
        }
        do {
            //vytáhneme si data z url,kde je výpis všech byt
            $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            ]);
            $request = $client->get($source);
            $html = (string) $request->getBody();
            $crawler = new Crawler($html);
            //pokud je vše ok, vytvoříme crawler filter najednotlivé "dlaždice" bytů
            $lastbuttons = $crawler->filter('li.page-item:last-of-type')->text();
            $ok = $lastbuttons === "Další";
            if ($ok) {
                //projedeme jednotlivé byty ve filteru
                $apartments = $crawler->filter('.Section_section__gjwvr > article')
                    ->each(static function (Crawler $item): apartment {
                        //name je v dlaždici vždy v <h2>
                        $name = $item->filter('h2')->text();
                        //vytáhneme odkaz na stránku s podobným výpisem informací o bytu
                        [$href, $text] = $item->filter('h2 > a')->extract(['href', '_text'])[0];
                        //vytáhneme název inzerátu
                        $name = str_replace("Pronájem bytu", "Pronájem bytu ", $name);
                        //nastavíme pomocnou proměnnou
                        $nodes =  $item->filter('.PropertyPrice_propertyPrice__lthza > span');
                        $i = 0;
                        $rent = 0;
                        $price = 0;
                        foreach ($nodes as $n){
                            $class = $item->filter('.PropertyPrice_propertyPrice__lthza > span')->extract(["class"])[$i];
                            if ($class === "PropertyPrice_propertyPriceAmount__WdEE1") $rent = preg_replace('/\D+/', "", $n->textContent);
                            if ($class === "PropertyPrice_propertyPriceAdditional__5jYQ6")$price = preg_replace('/\D+/', "", $n->textContent);
                            $i++;
                        }

                        $finalprice = $rent+$price;
                        $parts = $name;
                        $part = explode(",", $parts)[1];
                        //a nastavíme ji také do longpartu - bude potřeba při vkládání do DB
                        $longpart = $part;
                        //rozdělíme část Prahy na ulice a část do pole
                        if (strpos(",", $part)){
                            $partsplitted = explode(",", $part);
                            $finalpart = "";
                            //projedeme pole hodnot - ulice, část, popř. se zde může objevit okres
                            foreach ($partsplitted as $ps) {
                                //pokud je část ve formátu Praha - Něco, použijeme ho. Pokud ne, nic se nestane.
                                if (strpos($ps, "-")) $finalpart = $finalpart.$ps;
                            }
                        }
                        else{
                            $finalpart = $part;
                        }
                        //provedeme regexem replace klíčových slov - Okres Praha, Praha 1-22 a Praha -. Zůstanem nám tedy jen část, např. Holešovice.
                        $finalpart = str_replace(" Praha", "Praha", $finalpart);
                        $finalpart = preg_replace("/Praha (\d+)( -) /", "", $finalpart);
                        $finalpart = str_replace("Praha - ", "", $finalpart);
                        $finalpart = str_replace(", okres Praha", "", $finalpart);
                        //vrátíme object Apartment
                        $finalpart = str_replace(" Praha - ", "", $finalpart);
                        $a = new Apartment($href, $name, $href, (int) ($finalprice - $rent), (int) $finalprice, $finalpart, $longpart);
                        print_r($a);
                        return
                           $a;
                    });
                $i++;
                //nastavíme další stránku na výpisu, která se má projít
                $nextpage = strstr($source, '&page=', true);
                $nextpage .= "&page=" . $i;
                $source = $nextpage;
                //zkopírujeme obsah bytů na aktuální stránce do pole $finalapartments, kde se nachází všechny byty ze všech stránek.
                $finalapartments = array_merge($finalapartments, $apartments);
            }
        }
        while($ok);
        //vrátíme, o který reader se jednalo
        return new ApartmentsResult('bezrealitky', $finalapartments);
    }


    //tato metoda získává detaily bytu
    public function getDetails(): array {
        $db = $this->db->getConnection();
        $apartments_all = [];
        //vybereme jen vyety ze zdroje bezrealitky
        $allapartments = $db->query("select * from byty where url like '%bezrealitky%' and DATE(imported) = DATE(NOW())")->fetch_all(MYSQLI_ASSOC);
        //projdeme všechny byty z databáze
        foreach ($allapartments as $a) {
            //vytáhneme data z url detailu bytu
            $html = @file_get_contents($a["url"]);
            if (FALSE === $html) continue;
            $crawler = new Crawler(file_get_contents($a["url"]));
            $url= $a["url"];
            $apartments_all[] = $crawler->filter('.ContentBox_contentBox__tD7YI')
                ->each(static function (Crawler $item) use ($url): Apartment_detailed {
                    //filtrujeme první tabulku
                    $data = $item->filter('div > section > div > div:nth-child(1) > table > tbody > tr')
                        ->each(static function (Crawler $line) : ?array {
                            //projdeme data v tabulce a porovnáme, zda obsahují požadovaný údaj. Pokud ano, uložíme do pole a vrátíme.
                            if (strpos($line->text(), "Podlaží") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Podlaží", "", $stairs);
                                return ["stairs" => (int) $stairs];
                            }
                            if (strpos($line->text(), "Dispozice") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice", "", $size);
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Vybaveno") !== FALSE) {
                                if (strpos($line->text(), "Částečně")) $furniture = "částečně zařízený";
                                else if (strpos($line->text(), "Nevybaveno")) $furniture = "nezařízený";
                                else $furniture = "zařízený";
                                return ["furniture" => $furniture];
                            }
                            if (strpos($line->text(), "Stav") !== FALSE) {
                                $condition = $line->text();
                                if (strpos($condition, "Dobrý")) $condition = "dobrý";
                                else $condition = str_replace("Stav", "", $condition);
                                return ["condition" => $condition];
                            }
                            if (strpos($line->text(), "m²") !== FALSE && (strpos($line->text(), "zahrádka") === FALSE && (strpos($line->text(), "Lodžie") === FALSE && strpos($line->text(), "Sklep") === FALSE && strpos($line->text(), "Terasa") === FALSE) && strpos($line->text(), "Balkón") === FALSE)) {
                                $area = $line->text();
                                $area = preg_replace('/\D+/', "", $area);
                                return ["area" => (int) $area];
                            }

                            if (strpos($line->text(), "Balkón")  !== FALSE) return ["balcony" => true];
                            if (strpos($line->text(), "Domácí mazlíčci vítáni") !== FALSE) return ["animals" => true];
                            return NULL;
                        });
                    //to samé s druhou tabulkou
                    $data2 = $item->filter('div > section > div > div:nth-child(2) > table > tbody > tr')
                        ->each(static function (Crawler $line): ?array {
                            if (strpos($line->text(), "Podlaží") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Podlaží", "", $stairs);
                                return ["stairs" => (int) $stairs];
                            }
                            if (strpos($line->text(), "Dispozice") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice", "", $size);
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Vybaveno") !== FALSE) {
                                if (strpos($line->text(), "Částečně")) $furniture = "částečně zařízený";
                                else if (strpos($line->text(), "Nevybaveno"))$furniture = "nezařízený";
                                else $furniture = "zařízený";
                                return ["furniture" => $furniture];
                            }
                            if (strpos($line->text(), "Stav") !== FALSE) {
                                $condition = $line->text();
                                if (strpos($condition, "Dobrý"))$condition = "Dobrý";
                                if (strpos($condition, "dobrý"))$condition = "Dobrý";
                                else $condition = str_replace("Stav", "", $condition);
                                return ["condition" => $condition];
                            }
                            //nutno vyfiltrovat data o velikosti balkonu, zahrádky, lodžie, sklepa...
                            if (strpos($line->text(), "m²") !== FALSE && (strpos($line->text(), "zahrádka") === FALSE && (strpos($line->text(), "Lodžie") === FALSE && strpos($line->text(), "Sklep") === FALSE && strpos($line->text(), "Terasa") === FALSE) && strpos($line->text(), "Balkón") === FALSE)) {
                                $area = $line->text();
                                $area = preg_replace('/\D+/', "", $area);
                                return ["area" => (int) $area];
                            }

                            if (strpos($line->text(), "Balkón")  !== FALSE) return ["balcony" => true];
                            if (strpos($line->text(), "Výtah")  !== FALSE) return ["elevator" => true];
                            if (strpos($line->text(), "Domácí mazlíčci vítáni") !== FALSE) return ["animals" => true];
                            return NULL;
                        });
                    //přidáme fetchnutá data do pole, které obsahuje data obou tabulek
                    $alldata = array_merge(...array_values(array_filter($data)),...array_values(array_filter($data2)));
                    foreach ($alldata as $key => $value) {
                        //pokud se hodnota nevrátila - např. ji zadavatel bytu nevyplnil, vrátíme prázdný string.
                        if ($value == NULL){
                            $alldata[$key] = "";
                        }
                    }
                    //vytvoříme nový apartment_detailed - byt s detaily
                    $ap_d =  new Apartment_detailed($url , $alldata["animals"] ?? NULL, $alldata["furniture"] ?? NULL, $alldata["elevator"] ?? NULL, $alldata["stairs"] ?? NULL, $alldata["condition"] ?? NULL, $alldata["size"] ?? NULL, $alldata["balcony"] ?? NULL, $alldata["area"] ?? NULL);
                    return $ap_d;
                });
        }

        //vrátíme pole všech dat
        return array_merge(...$apartments_all);
    }
}