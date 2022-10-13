<?php
namespace App\Read;

use App\database;
use App\ValueObject\Apartment;
use App\ValueObject\Apartment_detailed;
use App\ValueObject\ApartmentsResult;
use Symfony\Component\DomCrawler\Crawler;

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
            //pokud stránka není v url, resp. v geetu není uvedena, automaticky ji bereme jako první a dodáváme tuto informaci do odkazu
            if (!strpos($source, "&page")){
                $source .= "&page=1";
            }
            do {
                //vytáhneme si data z url,kde je výpis všech bytů
                $html = file_get_contents($source);
                //vytvoříme nový crawler, který nám pomůže data získat
                $crawler = new Crawler($html);
                //pokud je vše ok, vytvoříme crawler filter najednotlivé "dlaždice" bytů
                $ok = 0 < $crawler->filter('section.Section_section___TusU > article.propertyCard')->count();
                if ($ok) {
                    //projedeme jednotlivé byty ve filteru
                    $apartments = $crawler->filter('.Section_section___TusU > article')
                        ->each(static function (Crawler $item): apartment {
                            //name je v dlaždici vždy v <h2>
                            $name = $item->filter('h2')->text();
                            //vytáhneme odkaz na stránku s podobným výpisem informací o bytu
                            [$href, $text] = $item->filter('h2 > a')->extract(['href', '_text'])[0];
                            //vytáhneme cenu bytu
                            $rent = $item->filter('.PropertyPrice_propertyPrice__aJuok')->text();
                            //vytáhneme název inzerátu
                            $name = str_replace("Pronájem bytu", "Pronájem bytu ", $name);
                            //nastavíme pomocnou proměnnou
                            $prices = $rent;
                            //v ceně mohou být 2 ceny - nájem a poplatky. Rozdělíme je tedy a dáme do pole
                            $prices = explode("Kč", $prices);
                            //u obou cen replacneme mezery - např z 15 000 uděláme 150000 a získáme tak číslo
                            $prices[0] = preg_replace('/\D+/', "", $prices[0]);
                            $prices[1] = preg_replace('/\D+/', "", $prices[1]);
                            //sečteme obě ceny a získáme finální
                            $finalprice = (int)($prices[0]) + (int)($prices[1]);
                            //cena nájmu bez poplatků
                            $rent = (int)($prices[0]);
                            //vytáhnemez z výpisu část Prahy
                            $part = $item->filter(".PropertyCard_propertyCardHeadline__y3bhA > a > span:nth-child(2)")->text();
                            //a nastavíme ji také do longpartu - bude potřeba při vkládání do DB
                            $longpart = $part;
                            //rozdělíme část Prahy na ulice a část do pole
                            $partsplitted = explode(", ", $part);
                            $finalpart = "";
                            //projedeme pole hodnot - ulice, část, popř. se zde může objevit okres
                            foreach ($partsplitted as $ps) {
                                //pokud je část ve formátu Praha - Něco, použijeme ho. Pokud ne, nic se nestane.
                                if (strpos($ps, " - ")) {
                                    $finalpart = $finalpart.$ps;
                                }
                            }
                            //provedeme regexem replace klíčových slov - Okres Praha, Praha 1-22 a Praha -. Zůstanem nám tedy jen část, např. Holešovice.
                            $finalpart = preg_replace("/Praha (\d+)( -) /", "", $finalpart);
                            $finalpart = str_replace("Praha - ", "", $finalpart);
                            $finalpart = str_replace(", okres Praha", "", $finalpart);
                            //vrátíme object Apartment
                            return
                                new Apartment($href, $name, $href, $rent, $finalprice, $finalpart, $longpart);
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
         $allapartments = $db->query("select * from byty where url like '%bezrealitky%'")->fetch_all(MYSQLI_ASSOC);
        //projdeme všechny byty z databáze
        foreach ($allapartments as $a) {
            //vytáhneme data z url detailu bytu
            $html = @file_get_contents($a["url"]);

            if (FALSE === $html) {
                continue;
            }
            $crawler = new Crawler(file_get_contents($a["url"]));
            $url= $a["url"];
            $apartments_all[] = $crawler->filter('.ContentBox_contentBox--outline-grey-medium__c9w0k')
                ->each(static function (Crawler $item) use ($url): Apartment_detailed {
                    //filtrujeme první tabulku
                    $data = $item->filter('div > section > div > div:nth-child(1) > table > tbody > tr')
                        ->each(static function (Crawler $line) : ?array {
                            //projdeme data v tabulce a porovnáme, zda obsahují požadovaný údaj. Pokud ano, uložíme do pole a vrátíme.
                            if (strpos($line->text(), "Podlaží") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Podlaží", "", $stairs);
                                return ["stairs" => $stairs];
                            }
                            if (strpos($line->text(), "Dispozice") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice", "", $size);
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Vybaveno") !== FALSE) {
                                if (strpos($line->text(), "Částečně")){
                                    $furniture = "Částečně";
                                }
                                else if (strpos($line->text(), "Nevybaveno")){
                                    $furniture = "Nevybaveno";
                                }
                                else{
                                    $furniture = "Vybaveno";
                                }
                                return ["furniture" => $furniture];
                            }
                            if (strpos($line->text(), "Stav") !== FALSE) {
                                $condition = $line->text();
                                $condition = str_replace("Stav", "", $condition);
                                return ["condition" => $condition];
                            }
                            if (strpos($line->text(), "m²")  !== FALSE && (strpos($line->text(), "zahrádka")) === FALSE) {
                                $area = $line->text();
                                $area = preg_replace('/\D+/', "", $area);
                                return ["area" => $area];
                            }

                            if (strpos($line->text(), "Balkón")  !== FALSE) {
                                $balcony = true;
                                $balcony = preg_replace('/\D+/', "", $balcony);
                                return ["balcony" => $balcony];
                            }
                            if (strpos($line->text(), "Domácí mazlíčci vítáni") !== FALSE) {
                                $animals = 1;
                                return ["animals" => $animals];
                            }
                            return NULL;
                        });
                    //to samé s druhou tabulkou
                    $data2 = $item->filter('div > section > div > div:nth-child(2) > table > tbody > tr')
                        ->each(static function (Crawler $line): ?array {
                            //      print("//".$line->text()."");
                            if (strpos($line->text(), "Podlaží") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Podlaží", "", $stairs);
                                return ["stairs" => $stairs];
                            }
                            if (strpos($line->text(), "Dispozice") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice", "", $size);
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Vybaveno") !== FALSE) {
                                if (strpos($line->text(), "Částečně")){
                                    $furniture = "Částečně";
                                }
                                else if (strpos($line->text(), "Nevybaveno")){
                                    $furniture = "Nevybaveno";
                                }
                                else{
                                    $furniture = "Vybaveno";
                                }

                                return ["furniture" => $furniture];
                            }
                            if (strpos($line->text(), "Stav") !== FALSE) {
                                $condition = $line->text();
                                $condition = str_replace("Stav", "", $condition);
                                return ["condition" => $condition];
                            }
                            if (strpos($line->text(), "m²")  !== FALSE && (strpos($line->text(), "zahrádka")) === FALSE) {
                                $area = $line->text();
                                $area = preg_replace('/\D+/', "", $area);
                                return ["area" => $area];
                            }

                            if (strpos($line->text(), "Balkón")  !== FALSE) {
                                $balcony = true;
                                //u balkonu může být údaj o velikosti - např. 5m2. To náš ale nezajímá, regexem to replacneme.
                                $balcony = preg_replace('/\D+/', "", $balcony);
                                return ["balcony" => $balcony];
                            }
                            if (strpos($line->text(), "Výtah")  !== FALSE) {
                                $elevator = true;
                                $elevator = preg_replace('/\D+/', "", $elevator);
                                return ["elevator" => $elevator];
                            }

                            if (strpos($line->text(), "Domácí mazlíčci vítáni") !== FALSE) {
                                $animals = 1;
                                return ["animals" => $animals];
                            }
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