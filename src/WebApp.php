<?php

namespace App;

use App\Database;
use App\DataRenderer;
use App\Read\ReaderInterface;

class WebApp
{
    private Database $connection;

    private ReaderInterface $reader;

    private DataRenderer $renderer;

    //Tato třída je vlastně celá webová aplikace.

    public function __construct(DataRenderer $renderer) {
        $this->renderer = $renderer;
    }

    //tahle funkce se spouští při načtení stránky
    public function run(): void {
        //vytvoříme si instanci DataRenderu a načteme data do Smarty templatů
        $this->renderer->LoadDataForSmarty();
    }
}
