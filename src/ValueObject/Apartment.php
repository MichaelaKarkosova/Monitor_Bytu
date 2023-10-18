<?php
namespace App\ValueObject;

class Apartment {
    public ?string $id;

    public ?string $name;

    public ?string $url;

    public ?float $price;

    public ?float $pricetotal;

    public ?string $longpart;

    public ?string $part;

    public function __construct(?string $id, ?string $name, ?string $url, ?int $price, ?int $pricetotal, ?string $part, ?string $longpart){
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->price = $price;
        $this->pricetotal = $pricetotal;
        $this->part = $part;
        $this->longpart = $longpart;
    }
}