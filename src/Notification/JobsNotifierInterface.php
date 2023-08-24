<?php

namespace App\Notification;

interface JobsNotifierInterface {

    public function notify(array $urls) : void;

}