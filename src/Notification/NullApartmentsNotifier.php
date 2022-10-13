<?php

namespace App\Notification;

use App\Notification\ApartmentsNotifierInterface;

class NullApartmentsNotifier implements ApartmentsNotifierInterface {

    public function notify(array $urls): void{
    }

}