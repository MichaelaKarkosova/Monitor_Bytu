<?php

namespace App\Read;

use App\Read\ReaderInterface;
use App\ValueObject\ApartmentsResult;
use RuntimeException;

//chain rozhoduje, odkud se bude číst podle url.

class ReaderChain implements ReaderInterface {
    private array $readers;
    private ReaderInterface $activereader;

    public function __construct(array $readers) {
        $this->readers = $readers;
    }

    //při spuštění read se spouští reader
    public function read(string $source) : ApartmentsResult {
        //projde všechny ready, které jsme registrovaly v commandu.
        foreach ($this->readers as $reader) {
            //zkontroluje, zda z url lze číst - resp. zda obsahuje část url patřící k readeru
            if ($reader->canRead($source)) {
                //nastavíme aktivní reader na tento
                $this->activereader = $reader;
                //a čteme z něj
                return $reader->read($source);

            }
        }
        //vyhodíme chybu, pokud není nalezen vhodný reade.
        throw new RuntimeException('Can not resolve reader.');
    }
    public function getDetails(): array {
        //čteme detaily z aktivního readeru z předchozí metody.
        return $this->activereader->getDetails();
    }
}