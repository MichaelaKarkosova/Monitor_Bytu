<?php
namespace App\Read;

use App\database;
use App\ValueObject\Apartment;
use App\ValueObject\Apartment_detailed;
use App\ValueObject\ApartmentsResult;
use Symfony\Component\DomCrawler\Crawler;

//v této třídě čteme data z webu realityMix
class RealityMixReader implements ChainableReaderInterface {

    private database $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    public function canRead(string $source): bool {
        $needle = 'realitymix';
        //kontrola, zda URL obsahuje ulovdomov. Zároven se zde rozhoduje chain.
        return strpos($source, $needle) > 0;

    }

    public function read(string $source): ApartmentsResult {
        $i = 1;
        $finalapartments = [];
        //pokud stránka není v url, resp. v geetu není uvedena, automaticky ji bereme jako první a dodáváme tuto informaci do odkazu
        if (!strpos($source, "&stranka")){
            $source .= "&stranka=1";
        }
        do {
            //vytáhneme si data z url,kde je výpis všech bytů. Zároveň odebereme z veškerých reklam tag li.
            $html = file_get_contents($source);
            $html = str_replace('li class="rmix-acquisition-banner"', "", $html);
            $html = str_replace("li style=", "", $html);
                //vytvoříme nový crawler, který nám pomůže data získat
            $crawler = new Crawler($html);

            //pokud je vše ok, vytvoříme crawler filter najednotlivé "dlaždice" bytů
            $ok = 0 == $crawler->filter('.alert--info')->count();
            if ($ok) {
                //projedeme jednotlivé byty ve filteru
                $apartments = $crawler->filter('.advert-list-items__items li')
                    ->each(static function (Crawler $item) {
                         //vytáhneme cenu bytu
                         $rent = $item->filter(".advert-list-items__content-price")->text();
                         //vytáhneme část prahy
                         $part = $item->filter(".advert-list-items__content-address")->text();
                         $name = $item->filter('h2')->text();
                         $longpart = $part;
                         //vytáhneme odkaz na stránku s podobným výpisem informací o bytu
                         [$href, $text] = $item->filter('h2 > a')->extract(['href', '_text'])[0];
                         //vytáhneme název inzerátu
                         $name = str_replace("Pronájem bytu", "Pronájem bytu ", $name);
                         //nastavíme pomocnou proměnnou
                         $prices = $rent;
                         //v ceně mohou být 2 ceny - nájem a poplatky. Rozdělíme je tedy a dáme do pole
                         $prices = explode("Kč", $prices);
                         //u obou cen replacneme mezery - např z 15 000 uděláme 150000 a získáme tak číslo
                         $prices[0] = preg_replace('/\D+/', "", $prices[0]);
                         $prices[1] = preg_replace('/\D+/', "", $prices[1]);
                         $rent = is_numeric($prices[0]) ? (int) $prices[0] : NULL;
                         if ($rent === NULL) {
                            return NULL;
                         }
                         $finalprice = (int)($prices[0]) + (int)($prices[1]);
                         //cena nájmu bez poplatků
                         $rent = (int)($prices[0]);
                        //upravíme part z formátu Praha x, část na Praha x - část.
                         $part = preg_replace("/\d+(,)/","-", $part);
                         //rozdělíme část Prahy na ulice a část do pole
                         $partsplitted = explode(", ", $part);
                         $finalpart = "";
                         //projedeme pole hodnot - ulice, část, popř. se zde může objevit okres
                         foreach ($partsplitted as $ps) {
                             //pokud je část ve formátu Praha - Něco, použijeme ho. Pokud ne, nic se nestane.
                             if (strpos($ps, " - ")) {
                                 $finalpart = $finalpart . $ps;
                             }
                         }
                         //provedeme regexem replace klíčových slov - Okres Praha, Praha 1-22 a Praha -. Zůstanem nám tedy jen část, např. Holešovice.
                         $finalpart = preg_replace("/Praha (\d+)( -) /", "", $finalpart);
                         $finalpart = str_replace("Praha - ", "", $finalpart);
                         $finalpart = str_replace(", okres Praha", "", $finalpart);
                         //vrátíme object Apartment
                         $a = new Apartment($href, $name, $href, 0, $finalprice, $finalpart, $longpart);
                         return $a;
                    });
                $i++;
                $nextpage = strstr($source, '&stranka=', true);
                $nextpage .= "&stranka=" . $i;
                $source = $nextpage;
                //zkopírujeme obsah bytů na aktuální stránce do pole $finalapartments, kde se nachází všechny byty ze všech stránek.
                $finalapartments = array_merge($finalapartments, $apartments);
            }
        }
        while($ok);
        //vrátíme, o který reader se jednalo
        return new ApartmentsResult('realitymix', array_filter($finalapartments));
    }


    //tato metoda získává detaily bytu
    public function getDetails(): array {
        $db = $this->db->getConnection();
        $apartments_all = [];
        //vybereme jen byty ze zdroje realitymix
        $allapartments = $db->query("select url, imported from byty where url like '%realitymix%'")->fetch_all(MYSQLI_ASSOC);
        //projdeme všechny byty z databáze
        foreach ($allapartments as $a) {
            //vytáhneme data z url detailu bytu
            $crawler = new Crawler(file_get_contents($a["url"]));
            $url= $a["url"];
            $apartments_all[] = $crawler->filter('.advert-layout__information-wrapper')
                ->each(static function (Crawler $item) use ($url): Apartment_detailed {
                    //uložíme si poznámku
                    $poznamka = $item->filter("div:nth-child(1) > div")->text();
                    //projdeme všechny <li> tagy az  nich vytáhneme data - první tabulka
                    $data = $item->filter('div:nth-child(3) > div > ul > li')
                        ->each(static function (Crawler $line) : ?array {
                            if (strpos($line->text(), "Číslo podlaží v domě: ") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Počet podlaží objektu: ", "", $stairs);
                                return ["stairs" => $stairs];
                            }
                            if (strpos($line->text(), "Dispozice bytu: ") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice bytu: ", "", $size);
                                if (strpos($size, "atypické") && strpos($size, "atypický")){
                                    $size = "Atypický";
                                }
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Stav objektu") !== FALSE) {
                                $condition = $line->text();
                                $condition = str_replace("Stav objektu: ", "", $condition);
                                return ["condition" => $condition];
                            }
                            if (strpos($line->text(), "Celková podlahová plocha: ") !== FALSE) {
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
                    //to samé s druhou tabulkou
                    $data2 = $item->filter('div:nth-child(3) > div:nth-child(2) > ul > li')
                        ->each(static function (Crawler $line)  use ($poznamka): ?array {
                            if (strpos($line->text(), "Číslo podlaží v domě: ") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Číslo podlaží v domě: ", "", $stairs);
                                return ["stairs" => $stairs];
                            }
                            if (strpos($line->text(), "Dispozice bytu: ") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice bytu: ", "", $size);
                                if (strpos($size, "atypické") && strpos($size, "atypický")){
                                    $size = "Atypický";
                                }
                                return ["size" => $size];
                            }
                            if (strpos($line->text(), "Stav objektu") !== FALSE) {
                                $condition = $line->text();
                                $condition = str_replace("Stav objektu: ", "", $condition);
                                return ["condition" => $condition];
                            }
                            if (strpos($line->text(), "Celková podlahová plocha: ") !== FALSE) {
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
                            //pokusíme se přečíst zbytek dat
                            if (strpos($poznamka, "Nevybaven") || strpos($poznamka, "nevybaven")){
                                return ["furtniture" => "Nevybaveno"];
                            }
                            else if (strpos($poznamka, "vybaven") - strpos("není", $poznamka) < 15) {
                                return ["furtniture" => "Nevybaveno"];
                            }
                            else if (strpos($poznamka, "vybaven")){
                                if (strpos($poznamka, "plně") && strpos($poznamka, "vybaven") - strpos("není", $poznamka) < 15) {
                                    return ["furtniture" => "Vybaveno"];
                                }
                                else{
                                    return ["furtniture" => "Částečně"];
                                }
                            }
                            else if (strpos($poznamka, "nábytek")){
                                if (strpos($poznamka, "nábytek") - strpos("je", $poznamka) < 15 || strpos($poznamka, "je") - strpos("nábytek", $poznamka) < 15){
                                    return ["furtniture" => "Částečně"];
                                }
                            }
                            if (strpos($poznamka, "balkon") || (strpos($poznamka, "balkón"))){
                                return ["balcony" => 1];
                            }
                            if (strpos($poznamka, "výtah")){
                                return ["elevator" => 1];
                            }
                            return NULL;
                        });

                    //přidáme fetchnutá data do pole, které obsahuje data obou tabulek. pokud se hodnota nevrátila - např. ji zadavatel bytu nevyplnil, vrátíme prázdný string.
                    $alldata = array_merge(...array_values(array_filter($data)),...array_values(array_filter($data2)));
                    foreach ($alldata as $key => $value) {
                        if ($value == NULL){
                            $alldata[$key] = "";
                        }
                    }
                    //vytvoříme nový apartment_detailed - byt s detaily
                    $ap_d =  new Apartment_detailed($url , $alldata["animals"] ?? NULL, $alldata["furniture"] ?? NULL, $alldata["elevator"] ?? 0, $alldata["stairs"] ?? NULL, $alldata["condition"] ?? NULL, $alldata["size"] ?? NULL, $alldata["balcony"] ?? 0, $alldata["area"] ?? NULL);
                    return $ap_d;
                });
        }
        //vrátíme pole všech dat
        return array_merge(...$apartments_all);
    }

}