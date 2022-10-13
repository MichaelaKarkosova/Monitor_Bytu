<?php

namespace App\Template;

use App\Template\TemplateInterface;
use RuntimeException;
use Smarty;

//v této třídě se nastavují data do templatu.
final class SmartyTemplate implements TemplateInterface {
    private Smarty $smarty;

    private ?string $filename = NULL;

    private array $parameters = [];

    public function __construct(Smarty $smarty) {
        $this->smarty = $smarty;
    }
    //Do této metody přijde pole parametrů v associativním poli. Všechny parametry projdeme a nastavíme.
    //příklad:
    // $patro => 1,
    //$dispozice = "1+kk"
    public function setParameters(iterable $parameters): void {
        foreach ($parameters as $k => $v) {
            $this->addParameter($k, $v);
        }
    }

    //tato metoda nastaví parametr z metody setParameter
    public function addParameter(string $name, $value): void {
        $this->parameters[$name] = $value;
    }

    //nastavíme, o jakou šablonu se jedná. Např. filters.tpl
    public function setFile(string $filename): void {
        $this->filename = $filename;
    }

    //provedeme render šablony
    public function render(): void {
        //kontrola, zda šablona existuje
        if (NULL === $this->filename) {
            throw new RuntimeException('Missing template file.');
        }
        //projedeme parametry nastavené v $this->parameters a předáme je Smarty
        foreach ($this->parameters as $k => $v) {
            $this->smarty->assign($k, $v);
        }
        //nakonec šablonu zobrazíme
        $this->smarty->display($this->filename);
    }
}
