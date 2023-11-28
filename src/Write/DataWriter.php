<?php

declare(strict_types=0);

namespace App\Write;

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
error_reporting(E_ALL);

use Normalizer;
use App\Database;
use App\Notification\ApartmentsNotifierInterface;
use App\ValueObject\ApartmentsResult;
use DateTime;
use Date;
use DateTimeZone;

class DataWriter implements WriterInterface {

    protected Database $db;
    private ApartmentsNotifierInterface $apartmentsNotifier;

    public function __construct(Database $db, ApartmentsNotifierInterface $apartmentsNotifier){
       $this->db = $db;
       $this->apartmentsNotifier = $apartmentsNotifier;
    }

    ///zapisujeme data do databáze
    public function write(ApartmentsResult $reader): void {
        echo "Writing apartments...";
        $newUrls = [];
        $foundUrls = [];
        $imported = (new DateTime('now', new DateTimeZone('+0200')))->format('Y-m-d H:i:s');
        $data = [];
        //query, co vybírá všechny byty z idnesu
        $getExisting = "select url, imported from byty where url like '%" . $reader->type . "%'";
        $existingg = $this->db->getConnection()->query($getExisting)->fetch_all(MYSQLI_ASSOC);
        $existing = array_column($existingg, "url");
        //projdeme všechny byty v databázi
        foreach ($reader->apartments as $index => $apartment) {
        	  $apartment->part = str_replace("Praha - ", "", $apartment->part);
        	 $apartment->part = str_replace("Praha -", "", $apartment->part);
             $apartment->part = str_replace("Praha-", "", $apartment->part);
            //přepíšeme index na url
            $data[$apartment->url] = $apartment;
            //kontrola, jestli to už v databázi neexistuje
            $checkquery = "select * from byty where url=?";
            $stmt = $this->db->getConnection()->prepare($checkquery);
            $stmt->bind_param("s", $apartment->id);
            $stmt->execute();
            $stmt->store_result();
           if ($apartment->pricetotal == $apartment->price) $apartment->price = 0;
            //pokud byt existuje v databázi, provedeme update včetně novéhoi imported času. Pokud ne, vložíme ho.
            if ($existing != null && in_array($apartment->id, $existing)) {
                $bindparams = [$apartment->id, $apartment->name, $apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $apartment->longpart, $imported, $apartment->id];
                $sql = "update `byty` set id=?, name=?, url=?, price=?, pricetotal=?, part=?, longpart=?, imported=? where id=?";
                $stmt2 = $this->db->getConnection()->prepare($sql);
                $stmt2->bind_param("sssiissss", ...array_values($bindparams));
                $stmt2->execute();
            }
            else {
                $sql = "insert into byty (id, name, url, price, pricetotal, part, longpart, imported, first) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt2 = $this->db->getConnection()->prepare($sql);
                $bindparams = [$apartment->id, $apartment->name, $apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $apartment->longpart, $imported, $imported];
                $stmt2->bind_param("sssiissss", ...array_values($bindparams));
                $stmt2->execute();
                //přidáme do pole nových bytů
                $newUrls[] = $apartment->id;
            }
                                //zapíšeme cenu
        $this->writePrice($apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $imported);
            //přidáme do pole všech bytů
            $foundUrls[] = $apartment->id;
        }
        //$this->populateAverage();
        //pokud jsou nějaké nové byty, pošleme notifikaci
        if (count($newUrls) > 0){
            $this->apartmentsNotifier->notify($newUrls);
        }
        //we need to recalculate after EVERY write to keep average actual.
        $this->populateAverage();

    }

    //zde zapíšeme detaily do databáze - přijdou sem detaily jednoho bytu
    public function WriteDetails(array $values): void {
        $toUpdate = [];
        print_r($values);
        print_r("Writing details...");
        //projdeme pole dat a vložíme vše do databáze
        foreach ($values as $v) {
         //replacneme stav v condition, normalizujeme string - oprava bugu, kdy někdy přišlo ve výsledku "ý" a jindy unicode znaky 0079 + 0301
            if ($v->condition){
            $v->condition = Normalizer::normalize(trim(strtolower(str_replace([" stav", "\x08", "\r", "\n", "\t"], ["", "", "", "", ""], $v->condition))));
            }
             $v->condition = str_replace("hrubába", "hrubá stavba", $v->condition);
            //podmínka proti PRAVDĚPODOBNĚ vadným datům
            if ($v->area > 3 && $v->area < 400) {
                //úprava hodnot do jednoho stejného tvaru
                if ($v->size){
                    if (strpos(strtolower($v->size), "atypic")){
                        $v->size = "Atypický";
                    }
                    if (strpos(strtolower($v->size), "pokoj")){
                        $v->size= "Pokoj";
                    }
                }

                $db = $this->db->getConnection();
                $sql = "insert IGNORE into byty_detaily (byty_id, zvirata, vybaveni, patro, stav, dispozice, balkon, vymera, vytah, images) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssisssiss", ...array_values((array) $v));
                $stmt->execute();
            }

             $toUpdate[] = [
                "condition" => $v->condition, 
                "area" => $v->area,
                "size" => $v->size, 
                "furniture" => $v->furniture,
                "id" => $v->id];
        }
        foreach ($toUpdate as $key=>$value){
            $this->updatePrice($value["condition"], $value["area"], $value["size"], $value["furniture"], $value["id"]);
        }

    }
    //zápis do tabulky, kde jsou historie cen
    public function writePrice(string $url, ?float $price, ?float $pricetotal, ?string $part, string $date){
        $db = $this->db->getConnection();
        print_r($url);
        $sql = "insert into ceny(url, price, pricetotal, part, date) values (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("siiss", $url, $price, $pricetotal, $part, $date);
        $stmt->execute();
    }

    public function updatePrice(?string $conditionArg, ?int $areaArg, ?string $sizeArg, ?string $furnitureArg, string $id){
        print_r("!!!!! UPDATING PRICE !!!!!");
        $condition = $conditionArg ?? NULL;
        $size = $sizeArg ?? NULL;
        $condition = $furnitureArg ?? NULL;
        $area = $areaArg ?? NULL;
        $furniture = $furnitureArg ?? NULL;
        $db = $this->db->getConnection();
        $sql = "update ceny set stav = ?, area = ?, size = ?, furniture = ? where url = ? and DATE(date) = DATE(NOW())";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sisss", $condition, $area, $size, $furniture, $id);
        $stmt->execute();
    }


    public function populateAverage(){
        $db = $this->db->getConnection();
       $sql = "truncate byty.average";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $conditions = $this->getAllOf("stav", true);
        $parts = $this->getAllOf("part");
        unlink("data.json");
        foreach ($parts as $key=>$p){
            foreach ($conditions as $key=>$c){
                $part = $p[0];
                if ($part == "") $part = "NULL";
                $condition = $c[0];
                if ($condition == "") $condition = "NULL";
              $average =  $this->getPerM2($part, $condition);
              if ($average[1] == false || $average[0] === 0 ||  $average[0] === "0") continue;
                            print_r($average);
              $query = "insert into byty.average (part, stav, averageprice) values (?, ?, ?)";
              $stmt2 = $db->prepare($query);
              // print_r([$part, $condition, $average[0]]);
              $stmt2->bind_param("ssi", $part, $condition, $average[0]);
              $stmt2->execute();
             $jsondata =
                array("part" => $part,
                    "condition" => $condition,
                        "average" => $average[0]
                    );
            }
        }


    }

    public function putIntoJson($jsondata){
        $json = file_get_contents('data.json');
        $data = json_decode($json);
        $data[] = $jsondata;
        file_put_contents('data.json', json_encode($data));
    }

        public function getAllOf($value, $isDetail = false){
        $db = $this->db->getConnection();
        if ($isDetail) $table = "byty_detaily";
            else $table = "byty";
        $sql = "select DISTINCT $value from $table";
        $stmt = $db->prepare($sql);
        $stmt->execute();
       return $stmt->get_result()->fetch_all();
    }
    
    public function getPerM2($part, $stav, $sum = 0){
            $db_c = $this->db->getConnection();
            $sql = "select DISTINCT (b.pricetotal-b.price) as pricetotal, bd.vymera, b.url, (b.pricetotal-b.price)/bd.vymera as perm3 from byty b join byty_detaily bd on bd.byty_id = b.url where part like '%$part%' and stav='$stav' and b.pricetotal > 1000";
            if ($part === "NULL") $sql = str_replace("part like '%$part%'", "part is NULL", $sql);
            if ($stav === "NULL") $sql = str_replace("stav like '%$stav%'", "stav is NULL", $sql);
            $stmt = $db_c->prepare($sql);
            $stmt->execute();
            //vezmeme výsledky sql
            $res = $stmt->get_result();
            $result = $res->fetch_all();
            $count = mysqli_num_rows($res);
            foreach ($result as $row){
                $sum = $sum+(int) $row[3];
            }
            if ($count < 1 ) return [0, false];
            return [$sum/$count, true];
    }
}