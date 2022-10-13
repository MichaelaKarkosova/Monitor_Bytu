<?php

namespace App\Write;

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
        //projdeme pole dat a vložíme vše do databáze
        foreach ($values as $v) {
            //úprava hodnot do jednoho stejného tvaru
            if (strpos(strtolower($v), "dobrý") && !strpos(strtolower($v), "velmi")){
                $v = "Dobrý";
            }
            if (strpos($v, "dobrý stav")){
                $v = "Dobrý";
            }
            if (strpos(strtolower($v), "atypick")){
                $v = "Atypický";
            }
            if (strpos(strtolower($v), "pokoj")){
                $v = "Pokoj";
            }
            if (strpos($v, "pokoj")) {
                $size = "Pokoj";
            }
            $db = $this->db->getConnection();
            $sql = "insert IGNORE into byty_detaily (byty_id, zvirata, vybaveni, patro, stav, dispozice, balkon, vymera, vytah) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssisssis", ...array_values((array) $v));
            $stmt->execute();
        }
    }

}