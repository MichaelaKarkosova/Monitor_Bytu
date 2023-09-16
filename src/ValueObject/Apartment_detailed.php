<?php

declare(strict_types=1);

namespace App\ValueObject;


class Apartment_detailed {
    public ?string $id;

    public ?bool $animals;

    public ?string $furniture;

    public ?bool $elevator;

    public ?int $stairs;

    public ?string $condition;

    public ?string $size;

    public ?bool $balcony;

    public ?float $area;


    public function __construct(string $id, ?bool $animals, ?string $furniture, ?bool $elevator, ?int $stairs, ?string $condition, ?string $size, ?bool $balcony, ?int $area) {
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