<?php

namespace App\Notification;

interface ApartmentsNotifierInterface {

    public function notify(array $urls) : void;

}