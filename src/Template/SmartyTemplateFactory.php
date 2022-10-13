<?php

namespace App\Template;

use App\Template\TemplateInterface;
use Smarty;

//Zde se vytváří Smarty šablony.
final class SmartyTemplateFactory implements TemplateFactoryInterface {
    //metoda vytváří nový objekt interfacu TemplateFactory. Create může přijít buď bez parametrů, nebo s nimi.
    public function create(string $filename, ?iterable $parameters = NULL): TemplateInterface {
        $smarty = new Smarty();
        //nastavíme složku, kde se nachází templaty
        $smarty->setTemplateDir(__DIR__ . '/../View/templates');
        //nastavíme složku, kde se mají cachovat existující šablony
        $smarty->setCompileDir(__DIR__ . '/../../var/cache/templates');
        //vytvoříme nový template
        $template = new SmartyTemplate($smarty);
        //nastavíme soubor templatu ve formátu .tpl
        $template->setFile($filename);
        //pokud sem přijdou parametry, nastavíme je.
        if (NULL !== $parameters) {
            $template->setParameters($parameters);
        }
        //vrátíme objekt template.
        return $template;
    }
}