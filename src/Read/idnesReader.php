<?php

declare(strict_types=1);

namespace App\Read;

use App\database;
use App\Command\ReadCommand;
use App\Read\ChainableReaderInterface;
use App\Read\ReaderInterface;
use App\ValueObject\Apartment;
use App\ValueObject\Apartment_detailed;
use App\ValueObject\ApartmentsResult;
use Nette\Utils\Json;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Goutte\Client;


class idnesReader implements ChainableReaderInterface
{
    private database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function canRead(string $source): bool
    {
        $needle = 'idnes';
        return strpos($source, $needle) > 0;
    }

    public function read(string $source): ApartmentsResult
    {
        //čteme data z idnesu
        $i = 1;
        $finalapartments = [];
        do {
            //sleep nám pomáhá získat data tak, aby nedošlo k timeoutu ze strany idnesu
           sleep(15);
            //vytáhneme obsah webu
            try{
                 $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
                ]);
            }
            catch (exception $e){
                echo $e;
            }
            $request = $client->get($source);
            $html = (string) $request->getBody();
            $crawler = new Crawler($html);
            //projdeme všechny dlaždice s byty a jednotlivé prvky v nich
            $apartments = $crawler->filter('.c-products__list .c-products__item article a.c-products__link')
                ->each(static function (Crawler $item) use ($source): apartment {
                    //zjistíme url na detail bytu
                    [$href, $text] = $item->extract(['href', '_text'])[0];
                    //zjistíme cenu bytu
                    $price = $item->filter('.c-products__price')->text();
                    //v ceně uděláme replace všeho, co není číslo - např. může nastat situace, kdy je uvedeno 15 000 kč, 15 000,- apod.
                    $price = preg_replace('/\D+/', "", $price);
                    //získáme informaci i lokalitě, resp. městské části
                    $part = $item->filter('.c-products__info')->text();
                    //v části provedeme replace, pokud je tam Okres Praha nebo okres Praha.
                    $part = str_replace(", okres Praha", "", $part);
                    $part = str_replace("okres Praha", "", $part);
                    $finalpart = "";
                    //část uložíme jako longstring, který budeme potřebovat do databáze
                    $longpart = $part;
                    //rozdělíme si část do pole - v adrese může být i ulice, což mi nechceme používat.
                    $partsplitted = explode(", ", $part);
                    //projdeme pole obsahující část
                    foreach ($partsplitted as $ps) {
                        //pokud je tam -, tak to znamená Praha - část. Např. Praha - Holešovice, tak ji přidáme k prázdné hodnotě.
                        if (strpos($ps, " - ")) {
                            $finalpart = $finalpart . $ps;
                        }
                    }
                    //uděláme replace Praha 1-22, výsledek bude tedy např. jen Holešovice.
                    $finalpart = preg_replace("/Praha (\d+)( -) /", "", $finalpart);
                    //vyfiltrujeme nadpis.
                    $title = $item->filter('.c-products__title')->text();
                    //vytvoříme nový objekt Apartment a vrátíme ho v annonymní funkci
                    $a = new Apartment($href, $title, $href, 0, (int) $price, $finalpart, $longpart);
                    return $a;
                });
            //spojíme všechny vrácené objekty do jednoho pole
            $finalapartments = array_merge($finalapartments, $apartments);
            //zkontrolujeme, zda je na webu přítomno tlačítko "další". Pokud ano, pokračujeme ve čtení další stránky.
            $source = $crawler->filter('.paginator .paging__item.next')->extract(['href'])[0] ?? NULL;
            $source = NULL !== $source ? 'https://reality.idnes.cz' . $source : NULL;
            $i++;
        } while ($source !== NULL);
        //vrátíme výsledek čtení - pole a informaci, z jakého zdroje se četlo.
        return new ApartmentsResult('idnes', $finalapartments);
    }


    //v této metodě získáváme detaily bytů
    public function getDetails(): array {
        $appartments_d = [];
        $db = $this->db->getConnection();
        //vytáhneme všechny idnes adresy na detaily z databáze
        $allapartments = $db->query("select * from byty where url like '%idnes%'");
        foreach ($allapartments as $a) {
             $html = @file_get_contents($a["url"]);

            if (FALSE === $html) {
                continue;
            }
            $url = $a['url'];
            //vytvoříme crawler na url z databáze
            $crawler = new Crawler(file_get_contents($a['url']));
            //
            $size = $crawler->filter('header.b-detail .b-detail__title')->text("");
            //Na idnesu se uvádí dispozice bytu v nadpise. Nadpis tedy rozdělíme na jednotlivé informace.
            $sizesplitted = explode(" ", $size);
            //projdeme nadpis, resp. informace v něm slovo po slově
            foreach ($sizesplitted as $s) {
                //pokud se jedná o velikost, nastavíme proměnnou $size na hodnotu z pole
                if (strpos($s, "Garso") || strpos($s, "kk") || strpos($s, "+")) {
                    $size = $s;
                }
            }
            //filtrujeme poznámku
            $poznamka = $crawler->filter('header.b-detail .wrapper-price-notes')->text("");
            //vezmem attributy, resp. parametry bytu
            $attributes = array_combine(
                $crawler->filter('article .b-definition-columns dl dt')->extract(['_text']),
                $crawler->filter('article .b-definition-columns dl dd')->each(static fn(Crawler $attrValue): Crawler => $attrValue)
            );

            foreach ($attributes as $attrName => $valueNode) {
                //pokud nalezneme výtah a za ním fajfku, vrátíme 1, pokud ne, tak 0
                if ('Výtah' === $attrName) {
                    $attributes[$attrName] = 0 < $valueNode->filter('.icon--check')->count() ? 1 : 0;
                    continue;
                }
                //pokud nalezneme balkon a za ním fajfku, vrátíme 1, pokud ne, tak 0
                if ('Balkon' === $attrName) {
                    $attributes[$attrName] = 0 < $valueNode->filter('.icon--check')->count() ? 1 : 0;
                    continue;
                }
                $attributes[$attrName] = $valueNode->text('');
            }
            //z attributů vezmeme jednotlivé hodnoty. Pokud neexistují, nastavíme na null.
            $price = $attributes['Cena'] ?? NULL;
            $elevator = $attributes['Výtah'] ?? NULL;
            $balcony = $attributes['Balkon'] ?? NULL;
            $condition = $attributes['Stav bytu'] ?? NULL;
            $stairs = $attributes['Podlaží'] ?? NULL;
            $furniture = $attributes['Vybavení'] ?? NULL;
            $area = $attributes['Užitná plocha'] ?? NULL;
            //ve výměře uděláme replace měrné jednotky m² a všech znaků, co nejsou čísla
            $area = str_replace("m²", "", $area);
            $area = str_replace("m2", "", $area);
            $area = preg_replace('/\D+/', "", $area);
            //nastavíme mazlíčky defaultně na null
            $animals = NULL;
            //rozlišíme, zda se jedná o pokoj. Formát pronájmu pokoje je "Pronájem pokoje xx m2". Nastavíme to pouze na "pokoj".
            if (strpos($size, "pokoj")) {
                $size = "Pokoj";
            }
            //pokusíme se přečíst, zda jsou v bytě povolení mazlíčci. Pokud zjistíme výskyt těchto frází v určité vzdálenosti od sebe, vrátíme 0, jako zakázano.
               $animals = $this->checkAnimals($poznamka);
            if ($stairs > 0){
                $stairs = strstr($stairs, '(', true);
                $stairs = (int) preg_replace('/\D+/', "", $stairs);
            }
            //vytvoříme nový objekt a přidáme ho do pole detailů
            $thisapartment = new Apartment_detailed($url, (bool) $animals ?? NULL, $furniture ?? NULL, (bool) $elevator ?? NULL, (int) $stairs ?? NULL, $condition ?? NULL, $size ?? NULL, (bool) $balcony ?? NULL, (int) $area ?? NULL);
            $appartments_d[] = $thisapartment;
        }
        //a celé pole vrátíme
        return $appartments_d;
    }

    protected function checkAnimals(string $note) {
        if ($this->checkForString($note,"bez", "mazlíčk")) return false;
        else if ($this->checkForString($note,"mazlíč", "vítá")) return true;
        else if ($this->checkForString($note,"zvíř", "nevadí")) return true;
        else if ($this->checkForString($note,"mazlíč", "nevadí")) return true;
        else if ($this->checkForString($note,"bez", "zvíř")) return false;
        else return null;
    }

    protected function checkForString(string $string, string $first, string $second) {
        $regexBody = '' === $first ? preg_quote($second) : ($first . '[^' . preg_quote($first) . ']{0,30}\s' . preg_quote($second));
        $regex = '/' . $regexBody . '/miu';
        return preg_match_all($regex, $string);
    }
}