<?php

namespace App\Command;

use App\Read\BezRealitkyReader;
use App\Database;
use App\Read\idnesReader;
use App\Read\UlovDomovReader;
use App\Read\ReaderChain;
use App\Read\ReaderInterface;
use App\Read\RealityMixReader;
use App\Write\WriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class AverageCommand extends Command  {
     protected ReaderInterface $reader;
     protected WriterInterface $writer;
     protected Database $db;

     public function __construct($writer) {
         parent::__construct();
         $this->writer = $writer;
     }

    //tato metoda se spouští při zaregistrování příkazu
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln("I run");
        $this->writer->populateAverage();
        return Command::SUCCESS;
    }

    protected function configure(): void {
         $this->setName("app:average");
    }

}