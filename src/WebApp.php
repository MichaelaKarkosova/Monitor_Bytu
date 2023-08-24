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
      //  $this->pullXmlObjBlogExample("www.google.com", "h2");
    }

    public function pullXmlObjBlogExample($siteUrl,$cssSelector){

        //create configuration object containing jquery selector and target site url to pass to the phantom script

        $config = array(
            "selector"=>$cssSelector,
            "url"=>$siteUrl
        );

        //read in the base phantom script and create a copy of it so we don't mess with the original base script

        $templateScript = "phantomJsBlogExample.js";
        $templateFileCopy = "phantomJsBlogExample-copy-".time().".js";

        if (!copy($templateScript, $templateFileCopy)) {
            echo "failed to copy $templateFileCopy";
            return false;
        }

        //Prepend configuration object onto script

        $configObj = file_get_contents($templateFileCopy);
        $configObj = 'var config = ' . json_encode($config,JSON_UNESCAPED_SLASHES). ';' . "\n" . $configObj;

        file_put_contents($templateFileCopy,$configObj);

        //Run the phantom script with php exec function, redirect output of script to an $output array;

        echo exec("phantomjs $templateFileCopy 2>&1",$output);

        //delete the copied version of the phantom script as we don't need it anymore

        if ( !unlink( $templateFileCopy ) ) {
            echo "failed to delete $templateFileCopy";
            return false;
        }

        // The first element of the output will be message about adding jquery and the last element will be the 'EXIT' message from the script,
        // lets remove those so all we have is the html lines

        array_shift($output);
        array_pop($output);

        //remove any whitespace from the array elements and join all the html lines into one string of all the html

        $output= array_map('trim', $output);
        $output = join("",$output);

        //construct an XML element from the html string

        $xmlObj = new \SimpleXMLElement($output);

        return $xmlObj;
    }
}
