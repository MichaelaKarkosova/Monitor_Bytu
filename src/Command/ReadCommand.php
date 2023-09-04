<?php

namespace App\Command;

use App\Read\BezRealitkyReader;
use App\Database;
use App\Read\idnesReader;
use App\Read\ReaderChain;
use App\Read\ReaderInterface;
use App\Read\RealityMixReader;
use App\Write\WriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class ReadCommand extends Command  {
     protected ReaderInterface $reader;
     protected WriterInterface $writer;
     protected Database $db;

     public function __construct(ReaderInterface $reader, WriterInterface $writer, Database $db) {
         parent::__construct();
         $this->reader = $reader;
         $this->writer = $writer;
         $this->db = $db;
     }

    //tato metoda se spouští při zaregistrování příkazu
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $ok = true;
        //kontrola validní URL
        if ($ok) {
            $output->writeln("URL " . $input->getArgument('arg') . " je validni");
        } else {
            $output->writeln("URL " . $input->getArgument('arg') . " není validní!");
        }
        //vytvoříme chain readerů
        $reader = new ReaderChain([
            new idnesReader($this->db),
            new BezRealitkyReader($this->db),
            new RealityMixReader($this->db)
        ]);
        //result spouští read na argument v příkazu - url na stránku s byty
        $result = $reader->read($input->getArgument('arg'));
        //po přečtení zapíšeme do tabulky byty
        $this->writer->write($result);
        //po zapsání do bytů přečteme data z detailů bytů
        $data = $reader->getDetails();
        //a data z bytů zapíšeme
        $this->writer->writeDetails($data);
        //vrátíme informaci, že je příkaz úspěšný
        return Command::SUCCESS;
    }

    protected function configure(): void {
         //náš příkaz je app:read
         $this->setName("app:read");
         //a vyžaduje parametr
         $this->addArgument('arg', InputArgument::REQUIRED, "argument");
    }

}