<?php

namespace App\User;

class User {
    public string $username;
    public string $password;
    public bool $verified;
    public string $firstname;
    public string $lastname;
    public string $gender;


    public function __construct(string $firstname, string $lastname, bool $verified, string $gender, string $username, string $password) {
        $this->password = $password;
        $this->firstname = $firstname;
        $this->gender = $gender;
        $this->lastname = $lastname;
        $this->password = $password;
        $this->username = $username;
        $this->verified = $verified;
    }
}