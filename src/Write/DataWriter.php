<?php
declare(strict_types=1);

namespace App\Write;

use Normalizer;
use App\Database;
use App\Notification\ApartmentsNotifierInterface;
use App\ValueObject\ApartmentsResult;
use DateTime;
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
        $imported = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
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
            //pokud byt existuje v databázi, provedeme update včetně novéhoi imported času. Pokud ne, vložíme ho.
            if ($existing != null && in_array($apartment->id, $existing)) {
                $bindparams = [$apartment->id, $apartment->name, $apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $apartment->longpart, $imported, $apartment->id];
                $sql = "update `byty` set id=?, name=?, url=?, price=?, pricetotal=?, part=?, longpart=?, imported=? where id=?";
                $stmt2 = $this->db->getConnection()->prepare($sql);
                $stmt2->bind_param("sssiissss", ...array_values($bindparams));
                $stmt2->execute();
            }
            else {
                $sql = "insert IGNORE into byty (id, name, url, price, pricetotal, part, longpart, imported) values (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt2 = $this->db->getConnection()->prepare($sql);
                $bindparams = [$apartment->id, $apartment->name, $apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $apartment->longpart, $imported];
                $stmt2->bind_param("sssiisss", ...array_values($bindparams));
                $stmt2->execute();
                //přidáme do pole nových bytů
                $newUrls[] = $apartment->id;
                //zapíšeme cenu
                $this->writePrice($apartment->url, $apartment->price, $apartment->pricetotal, $apartment->part, $imported);
            }
            //přidáme do pole všech bytů
            $foundUrls[] = $apartment->id;
        }
        //pokud jsou nějaké nové byty, pošleme notifikaci
        if (count($newUrls) > 0){
            $this->apartmentsNotifier->notify($newUrls);
        }
    }

    //zde zapíšeme detaily do databáze - přijdou sem detaily jednoho bytu
    public function WriteDetails(array $values): void {
        echo "Writing details...";
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
                $sql = "insert IGNORE into byty_detaily (byty_id, zvirata, vybaveni, patro, stav, dispozice, balkon, vymera, vytah) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssisssis", ...array_values((array) $v));
                $stmt->execute();
            }
        }
    }
    //zápis do tabulky, kde jsou historie cen
    public function writePrice(string $url, ?float $price, ?float $pricetotal, ?string $part, string $date){
        $db = $this->db->getConnection();
        $sql = "insert into ceny(url, price, pricetotal, part, date) values (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("siiss", $url, $price, $pricetotal, $part, $date);
        $stmt->execute();
    }

}