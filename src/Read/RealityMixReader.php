<?php

declare(strict_types=1);

namespace App\Read;
date_default_timezone_set("Europe/Prague");
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
            $ok = $crawler->filter('.paginator__list-item--disabled a i.icon-chevron-right')->count() < 1;
            if ($ok) {
                $apartments = $crawler->filter('.advert-item__content-data')
                    ->each(static function (Crawler $item) {
                        //vytáhneme cenu bytu
                        $rent = $item->filter("div.text-xl.font-extrabold")->text();
                        //vytáhneme část prahy
                        $part = $item->filter(".advert-item__content-data > div > p")->text();
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
                        // $prices[1] = preg_replace('/\D+/', "", $prices[1]);
                        $rent = is_numeric($prices[0]) ? (int) $prices[0] : NULL;
                        if ($rent === NULL) return NULL;
                        $finalprice = $rent;
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
                            if (strpos($ps, " - ")) $finalpart = $finalpart . $ps;
                        }
                        //provedeme regexem replace klíčových slov - Okres Praha, Praha 1-22 a Praha -. Zůstanem nám tedy jen část, např. Holešovice.
                        $finalpart = preg_replace("/Praha (\d+)( -) /", "", $finalpart);
                        $finalpart = str_replace("Praha - ", "", $finalpart);
                        $finalpart = str_replace(", okres Praha", "", $finalpart);
                        //vrátíme object Apartment
                        $a = new Apartment($href, $name, $href, 0, $rent, $finalpart, $longpart);
                        //  print_r($a);
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
        $allapartments = $db->query("select url, imported from byty where url like '%realitymix%' and DATE(imported) = DATE(NOW())")->fetch_all(MYSQLI_ASSOC);
        //projdeme všechny byty z databáze
        foreach ($allapartments as $a) {
            //vytáhneme data z url detailu bytu
            $crawler = new Crawler(file_get_contents($a["url"]));
            $url= $a["url"];
            $mainimage =  $crawler->filter('.advert-layout__gallery-wrapper .gallery__main-img > a > img')->extract(['src']);
            $visibleImages = $crawler->filter('.advert-layout__gallery-wrapper .gallery__small-img .gallery__item--image > a > img')->extract(['src']);
            $hiddenImages = $crawler->filter('.advert-layout__gallery-wrapper .gallery__small-img .gallery__hidden-items > a.gallery__item')->extract(['href']);
            $links[$url] = [...$visibleImages, ...$hiddenImages, ...$mainimage];

           // print_r( $links[$url] );
          //  print_r($crawler->filter('.advert-layout__gallery-wrapper > .gallery > .gallery__items > div > a > img'));
            $apartments_all[] = $crawler->filter('.advert-layout__information-wrapper')
                ->each(function (Crawler $item) use ($url, $links): Apartment_detailed {
                    //uložíme si poznámku
                    $poznamka = $item->filter("div > div")->text();
                    //projdeme všechny <li> tagy az  nich vytáhneme data - první tabulka

                    $data = $item->filter('div:nth-child(3) > div > ul > li')
                        ->each(static function (Crawler $line) use ($poznamka): ?array {
                            if (strpos($line->text(), "Číslo podlaží v domě: ") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Číslo podlaží v domě: ", "", $stairs);
                                return ["stairs" => (int) $stairs];
                            }
                            if (strpos($line->text(), "Dispozice bytu: ") !== FALSE) {
                                $size = $line->text();
                                $size = str_replace("Dispozice bytu: ", "", $size);
                                if (strpos($size, "atypické") && strpos($size, "atypický")) $size = "Atypický";
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
                                return ["area" => (int) $area];
                            }
                            return NULL;
                        });

                    //to samé s druhou tabulkou
                    $data2 = $item->filter('div:nth-child(3) > div:nth-child(2) > ul > li')
                        ->each(function (Crawler $line)  use ($poznamka): ?array {
                            if (strpos($line->text(), "Číslo podlaží v domě: ") !== FALSE) {
                                $stairs = $line->text();
                                $stairs = str_replace("Číslo podlaží v domě: ", "", $stairs);
                                return ["stairs" => (int) $stairs];
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
                                return ["area" => (int) $area];
                            }
                            if (strpos($line->text(), "Balkón")  !== FALSE) return ["balcony" => true];
                            if (strpos($line->text(), "Výtah")  !== FALSE) return ["elevator" => true];
                            if (strpos($poznamka, "výtah")) return ["elevator" => true];
                            if (strpos($line->text(), "balkon") !== FALSE) return ["balcony" => true];
                            $animals = $this->checkAnimals($poznamka);
                            $balcony = $this->checkForString($poznamka, "", "balkon");
                            if (!$balcony) $balcony = $this->checkForString($poznamka, "", "balkón");
                            $elevator= $this->checkForString($poznamka, "", "výtah");
                            $furniture = $this->checkFurniture($poznamka);
                            return ["animals" => $animals, "furniture" => $furniture, "balcony" => (bool) $balcony, 'elevator' =>  (bool)  $elevator];
                        });
                    //přidáme fetchnutá data do pole, které obsahuje data obou tabulek. pokud se hodnota nevrátila - např. ji zadavatel bytu nevyplnil, vrátíme prázdný string.
                    $images = json_encode($links[$url]);
                    $alldata = array_merge(...array_values(array_filter($data)),...array_values(array_filter($data2)));
                    $ap_d =  new Apartment_detailed($url , $alldata["animals"] ?? NULL, $alldata["furniture"] ?? NULL, $alldata["elevator"] ?? false, $alldata["stairs"] ?? NULL, $alldata["condition"] ?? NULL, $alldata["size"] ?? NULL, $alldata["balcony"] ?? false, $alldata["area"] ?? NULL, $images ?? NULL);
                    return $ap_d;
                });
        }
        //vrátíme pole všech dat
        return array_merge(...$apartments_all);
    }


    protected function checkAnimals(string $note) {
        if ($this->checkForString($note,"bez", "mazlíčk")) return false;
        else if ($this->checkForString($note,"mazlíč", "vítá")) return true;
        else if ($this->checkForString($note,"zvíř", "nevadí")) return true;
        else if ($this->checkForString($note,"mazlíč", "nevadí")) return true;
        else if ($this->checkForString($note,"bez", "zvíř")) return false;
        else return null;
    }

    protected function checkFurniture(string $note) {
        if ($this->checkForString($note,"plně", "vybaven")) $furniture = "zařízený";
        else if (!$this->checkForString($note,"vybaven", "nabytkem")) $furniture = "zařízený";
        else if (!$this->checkForString($note,"vybaven", "nábytkem")) $furniture = "zařízený";
        else if (!$this->checkForString($note,"obsahuje", "nabytek")) $furniture = "zařízený";
        else if (!$this->checkForString($note,"kompletně", "vybaven"))  $furniture = "zařízený";
        else if (!$this->checkForString($note,"částečně", "vybaven"))  $furniture = "částečně zařízený";
        else if (!$this->checkForString($note,"není", "vybaven"))  $furniture = "zařízený";
        else if (!$this->checkForString($note,"", "nevybaven"))  $furniture = "nezařízený";
        else $furniture = NULL;
        return $furniture;
    }
    protected function checkForString(string $string, string $first, string $second) {
        $regexBody = '' === $first ? preg_quote($second) : ($first . '[^' . preg_quote($first) . ']{0,30}\s' . preg_quote($second));
        $regex = '/' . $regexBody . '/miu';
        return preg_match_all($regex, $string);
    }
}