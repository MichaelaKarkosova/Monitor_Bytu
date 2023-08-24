<?php

namespace App\Notification;

use App\Notification\JobsNotifierInterface;

class NullJobsNotifier implements JobsNotifierInterface {

    public function notify(array $urls): void{
    }

}