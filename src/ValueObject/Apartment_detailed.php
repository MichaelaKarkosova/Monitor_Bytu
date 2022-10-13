<?php

namespace App\ValueObject;


class Apartment_detailed {
    public ?string $id;

    public ?string $name;

    public ?string $url;

    public ?float $price;

    public ?float $pricetotal;

    public ?string $animals;

    public ?string $furniture;

    public ?string $part;

    public ?int $stairs;

    public ?string $condition;

    public ?string $size;

    public ?string $balcony;

    public ?float $area;

    public ?string $elevator;

    public function __construct(string $id, ?string $animals, ?string $furniture, ?string $elevator, ?int $stairs, ?string $condition, ?string $size, ?string $balcony, ?int $area)
    {
        $this->id = $id;
        $this->animals = $animals;
        $this->furniture = $furniture;
        $this->elevator = $elevator;
        $this->stairs = $stairs;
        $this->condition = $condition;
        $this->size = $size;
        $this->balcony = $balcony;
        $this->area = $area;
    }

}