<?php

namespace App\Command;

use App\Database;
use App\Notification\ApartmentsNotifierInterface;
use DateTime;
use DateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends Command {

    public Database $db;

    public function __construct(Database $db){
        parent::__construct();
        $this->db = $db;
    }

    //spouští se ve formátu bin/console app:clear "-3 hours"
    protected function execute(InputInterface $input, OutputInterface $output) : int {
        //Datetime nastavím na teď a zároveň provedeme modify podle argumentu, např. odečteme 3h
        $datetime = (new DateTime($input->getArgument('datetimeModifier'), new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        //v DB smažeme vše, co je menší než datetime
        $stmt = $this->db->getConnection()->prepare("delete from byty where imported < ?");
        $stmt->bind_param("s", $datetime);
        $stmt->execute();
        return Command::SUCCESS;
    }

    protected function configure(): void {
        //náš příkaz je app:clear
        $this->setName("app:clear");
        //a vyžaduje parametr
        $this->addArgument('datetimeModifier', InputArgument::REQUIRED, "datetimeModifier");
    }
}