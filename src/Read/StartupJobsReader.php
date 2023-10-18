<?php

namespace App\Read;

use App\database;
use App\ValueObject\Job;
use App\ValueObject\JobsResult;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;


//v této třídě čteme data z webu bezrealitky
class StartupJobsReader implements ChainableReaderInterface
{

    private database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function canRead(string $source): bool
    {
        $needle = 'startupjobs';
        return strpos($source, $needle) > 0;

    }

    public function read(string $source): JobsResult
    {
        //https://www.startupjobs.cz/nabidky/vyvoj/
        $finaljobs = [];
        print_r($source);
        //pokud stránka není v url, resp. v geetu není uvedena, automaticky ji bereme jako první a dodáváme tuto informaci do odkazu
        if (!strpos($source, "&page=")) {
            $source .= "&page=1";
        }
        print_r($source);
        do {

            $headers = get_headers($source);
            print_r(substr($headers[0], 9, 3));
            $ok = substr($headers[0], 9, 3) === 200 && $source !== "www.startupjobs.cz";
            //vytáhneme si data z url,kde je výpis všech byt
            $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'decode_content' => false,
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            ]);
            $json = json_decode(file_get_contents($source), true);
            $count = count($json["resultSet"])-1;
            for ($i = 0; $i < $count; $i++) {
                $ok = $i < $count;
                if (strpos($json['resultSet'][$i]['locations'], "remote")) $remote = 1;
                $location = $json['resultSet'][$i]['locations'];
                $id = $json['resultSet'][$i]['id'];
                $collab = $json['resultSet'][$i]['collaborations'];
                $shifts = $json['resultSet'][$i]['shifts'];
                $name = $json['resultSet'][$i]['name'];
                print_r("Name je $name");
                $url = "http://startupjobs.cz".$json['resultSet'][$i]['url'];
                $details = $this->getDetails($id);
                $nextpage = strstr($source, '&page=', true);
                $nextpage .= "&page=" . $i;
                $source = $nextpage;
                $job = new Job($id, $url, $name, (int)$details['salary_min'], (int)$details['salary_max'], $details['seniority'], $details['remote'] ?? NULL, $details['location'] ?? NULL, "", $shifts, $collab);
                $finaljobs[] = $job;

            }
         } while ($ok);
        return new JobsResult('startupjobs', $finaljobs);
    }


    //tato metoda získává detaily práce
    public function getDetails(string $id): array
    {
        print_r("reading details from id $id");
        $data = [];
        $url = "https://www.startupjobs.cz/nabidka/" . $id;
        $headers = get_headers($url);
      //  print_r(substr($headers[0], 9, 3));
     //       $ok = substr($headers[0], 9, 3) === 301;
       //     if ($ok){
        sleep(2);
        $client = new \GuzzleHttp\Client(['headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate',
            'decode_content' => false,
            'Referer' => $url,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
        ]);
        $request = $client->get($url);
        $html = (string)$request->getBody();
        $crawler = new Crawler($html);
        //	print_r($crawler->filter('.offer-detail-parameters')->text());
        $jobs = $crawler->filter('.offer-detail-parameters')
            ->each(static function (Crawler $item) use ($url) {
                $salaryvar = $item->filter('div > div')->text();
                if (strpos($salaryvar, "-")) {
                    $salaries = explode("-", $salaryvar);
                    $salary_min = preg_replace('/[^0-9]/', '', $salaries[0]);
                    $salary_max = preg_replace('/[^0-9]/', '', $salaries[1]);
                }
                if (strpos($salaryvar, "hod")) {
                    $salary_max = $salary_max * 160;
                    $salary_min = $salary_min * 160;
                }
                $location = $item->filter('div > div > div > div:nth-child(1)')->text();
                $info = $item->filter('div > div:nth-child(1)')->text();
                $seniority = "";
                if (strpos($info, "senior")) {
                    $seniority .= "senior";
                }
                if (strpos($info, "medior")) {
                    if ($seniority == "") {
                        $seniority .= "medior";
                    } else {
                        $seniority .= ", medior";
                    }
                }
                if (strpos(strtolower($info), "remote")) {
                    $remote = 1;
                }

                //zjistíme url na detail bytu

                //  $a = new Job($href, $title, $href, 0, $price, $finalpart, $longpart);
                $returndata = ["seniority" => $seniority,
                    "remote" => $remote,
                    "location" => $location,
                    "salary_max" => $salary_max,
                    "salary_min" => $salary_min,
                ];

                return $returndata;
            });
        return array_merge(...$jobs);
    }

}