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
class SRealityReader implements ChainableReaderInterface {

    private database $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    public function canRead(string $source): bool {
        $needle = 'sreality.cz';
        //kontrola, zda URL obsahuje ulovdomov. Zároven se zde rozhoduje chain.
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
            //vytáhneme si data z url,kde je výpis všech bytů. Zároveň odebereme z veškerých reklam tag li.
            $html = file_get_contents($source);
            $html = str_replace('li class="rmix-acquisition-banner"', "", $html);
            $html = str_replace("li style=", "", $html);
            //vytvoříme nový crawler, který nám pomůže data získat
            $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            ]);

           $json = json_decode(file_get_contents($source), true);
           $count = count($json["_embedded"]["estates"])-1;
        //   print_r($json["_embedded"]["estates"]);
         //  print_r($count);
         $ok = $count > 0;
           //print_r($count);
          $alldata = $json["_embedded"]["estates"];
        
            //pokud je vše ok, vytvoříme crawler filter najednotlivé "dlaždice" bytů
            //print_r($crawler->filter('.paging-next.disabled')->count());
 
            // /cs/v2/estates/3006920012
           // print_r($crawler->filter(' "estates": ['));
           // print_r("Count je $count");
            //for ($i = 0; $i < $count; $i++) {
             if ($ok) {
          foreach ($alldata as $key=>$data){
            //print_r($data);
                $finalprice = $data["price"];
                $locality = $data["locality"];
                $name = $data["name"];
                $url = "https://www.sreality.cz/api".$data["_links"]["self"]["href"];
                            $parts = $locality;

                            $allparts = explode("-", $parts);
                            if (count($allparts) < 2) $part = $locality;
                            else $part = explode("-", $parts)[1];
                        //a nastavíme ji také do longpartu - bude potřeba při vkládání do DB
                        $longpart = trim($locality);
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
                        	print_r("part = finalpart");
                            $finalpart = $part;
                        }
                        //provedeme regexem replace klíčových slov - Okres Praha, Praha 1-22 a Praha -. Zůstanem nám tedy jen část, např. Holešovice.
                        $finalpart = preg_replace("/Praha (\d+)( -)/", "", $finalpart);
                        $finalpart = preg_replace("/Praha (\d+)/", "", $finalpart);
                        print_r("final part je $finalpart");
                        $finalpart = str_replace("Praha - ", "", $finalpart);
                        $finalpart = str_replace(",Praha", "", $finalpart);
                        $finalpart = str_replace(", Praha ", "", $finalpart);
                        $finalpart = str_replace(" Praha ", "", $finalpart);
                        $finalpart = str_replace(", okres Praha", "", $finalpart);
                        //vrátíme object Apartment
                        $finalpart = str_replace(" Praha - ", "", $finalpart);

                         $apartments[] =  new Apartment($url, $name, $url, (int) ($finalprice - 0), (int) $finalprice, trim($finalpart), trim($longpart));
                    }
                           $i++;
               //)) print_r([$url, $name, $finalpart, $price, $longpart]);
                //print_r($data[$i]);
                $nextpage = strstr($source, '&page=', true);
                $nextpage .= "&page=" . $i;
                $source = $nextpage;
               // print_r([$url, $name, $url, (int) ($finalprice - $price), 0, $finalpart, $longpart]);
                $finalapartments = array_merge($finalapartments, $apartments);
             //   $finalapartments[] = $ap;
             //   print_r($ap);
                //zkopírujeme obsah bytů na aktuální stránce do pole $finalapartments, kde se nachází všechny byty ze všech stránek.
            }
            // }
        }
        while($ok);
        //vrátíme, o který reader se jednalo
        return new ApartmentsResult('sreality', array_filter($finalapartments));
    }


    //tato metoda získává detaily bytu
    public function getDetails(): array {
        print_r("reading details!!!!!!");
        $db = $this->db->getConnection();
        $apartments_all = [];

            
        //vybereme jen byty ze zdroje realitymix
        $allapartments = $db->query("select url, imported from byty where url like '%sreality%' and DATE(imported) = DATE(NOW())")->fetch_all(MYSQLI_ASSOC);
        //projdeme všechny byty z databáze
        foreach ($allapartments as $a) {
            //vytáhneme data z url detailu bytu
                    $url= $a["url"];
            $context = stream_context_create(['http' => ['ignore_errors' => true]]);
            $result = file_get_contents($url, false, $context);
            print_r($http_response_header[0]);
            if (strpos($http_response_header[0], "200")){
               $json = json_decode(file_get_contents($url), true);
      
                $longpart = $json["locality"]["value"];
                $elevator = $json["recommendations_data"]["elevator"];
                $balcony = $json["recommendations_data"]["balcony"];
                $area = $json["recommendations_data"]["usable_area"];
                $conditionNum = (int) $json["recommendations_data"]["building_condition"];
                $furnishedNum = (int) $json["recommendations_data"]["furnished"];
                if ($furnishedNum == 3) $furniture = "částečně zařízený";
                if ($furnishedNum == 2) $furniture = "zařízený";
                if ($furnishedNum == 1) $furniture = "nezařízený";
                else $furniture = NULL;
                switch ($conditionNum){
                    case 1:
                        $condition = "velmi dobrý";
                        break;
                    case 2:
                        $condition = "dobrý";
                        break;
                    case 3:
                        $condition = "Špatný";
                        break;
                    case 4:
                        $condition = "ve výstavbě";
                        break;
                    case 5:
                        $condition = "projekt";
                        break;
                    case 8:
                        $condition = "před rekonstrukcí";
                        break;
                    case 6:
                        $condition = "novostavba";
                        break;
                    case 9:
                        $condition = "po rekonstrukci";
                        break;
                    case 10:
                        $condition = "v rekonstrukci";
                        break;
                    default:
                        $condition = NULL;
                        break;
                } 
                $desc = $json["meta_description"];
                $animals = $this->checkAnimals($desc);
              //  print_r($json["name"]["value"]);
                $size = $this->checkSize($json["name"]["value"]);
                $items = $json["items"];
                foreach ($items as $key=>$item){
                    if ($item["name"] === "Podlaží"){
                        $stairs = $item["value"][0];
                    }
                }
                 $apartment = new Apartment_detailed($url, (bool) $animals, $furniture, (bool) $elevator, (int) $stairs, $condition, $size, (bool) $balcony, (int) $area);
                 print_r($apartment);
                 $apartments_all[] = $apartment;
                }
}
        //vrátíme pole všech dat
        return $apartments_all ;
    }



    protected function checkSize(string $note) {
        if ($this->checkForString($note,"1+kk", "")) return "1+kk";
        else if ($this->checkForString($note,"1+1", "")) return "1+1";
        else if ($this->checkForString($note,"Garso", "")) return "Garsoniéra";
        else if ($this->checkForString($note,"2+1", "")) return "2+1";
        else if ($this->checkForString($note,"2+kk", "")) return "2+kk";
        else if ($this->checkForString($note,"3+1", "")) return "3+1";
        else if ($this->checkForString($note,"3+kk", "")) return "3+kk";
        else if ($this->checkForString($note,"4+1", "")) return "4+1";
        else if ($this->checkForString($note,"4+kk", "")) return "4+kk";
        else if ($this->checkForString($note,"5+1", "")) return "5+1";
        else if ($this->checkForString($note,"5+kk", "")) return "5+kk";
        else if ($this->checkForString($note,"6+", "")) return "6 a více";
        else if ($this->checkForString($note,"pokoj", "")) return "pokoj";
        else if ($this->checkForString($note, "kk", "")) return "1+kk";
        else if ($this->checkForString($note, "1+", "")) return "1+1";
        else return null;
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