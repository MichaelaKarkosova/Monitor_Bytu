<?php

namespace App\UserProfile;


class UserRegister{
    public function RegisterNewUser(string $firstname, string $lastname, bool $verified, string $gender, string $username, string $password) {
        $sql = "insert into inzerce (firstname, lastname, verified, gender, username, password) values (firstname=?, lastname=?, verified=?, gender=?, username=?, password=?)";
        $stmt2 = $this->db->getConnection()->prepare($sql);
        $params = [$firstname, $lastname, $verified, $gender, $username, $password];
        $stmt2->bind_param("ssbsss", ...array_values($params));
        $stmt2->execute();
    }
}