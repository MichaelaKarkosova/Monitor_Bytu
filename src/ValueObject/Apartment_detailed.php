<?php

namespace App\ValueObject;


class Apartment_detailed {
    public ?string $id;

    public ?int $name;

    public ?string $url;

    public ?float $price;

    public ?float $pricetotal;

    public ?bool $animals;

    public ?string $furniture;

    public ?string $part;

    public ?int $stairs;

    public ?string $condition;

    public ?string $size;

    public ?bool $balcony;

    public ?float $area;

    public ?bool $elevator;

    public ?string $images;

    public function __construct(string $id, ?bool $animals, ?string $furniture, ?bool $elevator, ?int $stairs, ?string $condition, ?string $size, ?bool $balcony, ?int $area, ?string $images)
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
        $this->images = $images;
    }

}