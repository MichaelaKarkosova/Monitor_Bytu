<?php
namespace App\Read;

use App\database;
use App\ValueObject\Job;
use App\ValueObject\JobsResult;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;


//v této třídě čteme data z webu bezrealitky
class StartupJobsReader implements ChainableReaderInterface {

    private database $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    public function canRead(string $source): bool {
        $needle = 'startupjobs';
        return strpos($source, $needle) > 0;

    }

    public function read(string $source): JobsResult {
        //https://www.startupjobs.cz/nabidky/vyvoj/
        $i = 1;
        $finaljobs = [];
        $ok = true;
        //pokud stránka není v url, resp. v geetu není uvedena, automaticky ji bereme jako první a dodáváme tuto informaci do odkazu
           if (!strpos($source, "/strana-")){
            $source .= "/strana-1";
        }

        do {
            //vytáhneme si data z url,kde je výpis všech byt
            $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'decode_content' => false,
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            ]);
            // $client->get();

            // var_dump($http_response_header); 	
            $request = $client->get($source);
            //$html = (string) $request->getBody();
            $json = json_decode(file_get_contents($source),true);
			//print_r($json['resultSet'][0]);
			if (strpos($json['resultSet'][0]['locations'], "remote")) $remote = 1;
			$location = $json['resultSet'][0]['locations'];
			$id = $json['resultSet'][0]['id'];
			$collab = $json['resultSet'][0]['collaborations'];
			$shifts = $json['resultSet'][0]['shifts'];
			$name = $json['resultSet'][0]['name'];
            //  $last = ($lastbuttons === "Další");
           // $ok = $lastbuttons === "Další";
            //   $ok = 0 < $crawler->filter('section.Section_section___TusU > article.propertyCard')->count();
            if ($ok) {
                $finaljobs = new Job($id, $name, $salary_from, $salary_to, $seniority, $remote, $location, $knowhow, $shifts, $collab);
                $this->getDetailss($id);
                $nextpage = strstr($source, '/strana-', true);
                $nextpage .= "/strana-" . $i;
                $source = $nextpage;
                //print_r($finaljobs);

                $finaljobs = array_merge($finaljobs, $jobs);
            }
        }
        while($ok);
        return new JobsResult('startupjobs', $finaljobs);
    }


    //tato metoda získává detaily bytu
    public function getDetailss(string $id): array {
    	$url = "https://www.startupjobs.cz/nabidka/".$id;
		 $client = new \GuzzleHttp\Client(['headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'decode_content' => false,
                'Referer' => $source,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            ]);
          $request = $client->get($url);
		$html = (string) $request->getBody();
		$crawler = new Crawler($html);
	//	print_r($crawler->filter('.offer-detail-parameters')->text());
		     $apartments = $crawler->filter('.offer-detail-parameters')
                ->each(static function (Crawler $item) use ($source): Job {
                	print_r($item->filter('div > div')->text());
                                   //zjistíme url na detail bytu
                  
                  //  $a = new Job($href, $title, $href, 0, $price, $finalpart, $longpart);
                    return $a;
                }); 	
      //  print_r(file_get_contents($url));
        return array_merge(...$jobs_all);
    }
       public function getDetails(): array {
        }

}