<?php
namespace App\ValueObject;


//$name, $salary_from, $salary_to, $seniority, $remote, $location, $knowhow, $shifts, $collab

class Job {
    public ?string $name;

    public ?int $salary_from;

    public ?int $salary_to;

    public ?string $seniority;

    public ?boolean $remote;

    public ?string $location;

    public ?string $knowhow;

    public string $shifts;

    public string $collab;

    public ?string $id;

    public function __construct(string $id, string $name, ?int $salary_from, ?int $salary_to, ?string $seniority, ?boolean $remote, ?string $location, ?string $knowhow, string $shifts, string $collab){
        $this->id = $id;
        $this->name = $name;
        $this->salary_to = $salary_to;
        $this->salary_from = $salary_from;
        $this->seniority = $seniority;
        $this->remote = $remote;
        $this->location = $location;
        $this->knowhow = $knowhow;
        $this->shifts = $shifts;
        $this->collab = $collab;
    }
}

