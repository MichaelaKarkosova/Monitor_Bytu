<?php

namespace App\Notification;

class DiscordApartmentsNotifier implements ApartmentsNotifierInterface {
    private const BATCH_SIZE = 20;

    private string $webhookUrl;

    public function __construct(string $webhookUrl) {
        $this->webhookUrl = $webhookUrl;
    }


    public function notify(array $urls): void {
        $batch = [];

        foreach ($urls as $url) {
            $batch[] = $url;

            if (count($batch) === self::BATCH_SIZE) {
                $this->doNotify($batch);

                $batch = [];
            }
        }

        if (0 < count($batch)) {
            $this->doNotify($batch);
        }
    }

    //metoda, co posílá pomocí webhooku notifikace na Discord - do zprávy vypíše seznam odkazů na nové byty
    private function doNotify($urls): void {
        $message = "";
        foreach ($urls as $url){
            $message = $message.$url."\n";
        }
        $data = array('content' => $message);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        file_get_contents($this->webhookUrl, false, stream_context_create($options));
        sleep(5);
    }
}