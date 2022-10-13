<?php

namespace App\Template;

use DateTime;
class ColorMode {

    public function getColorMode() : string {
        $mode = $_COOKIE["color_mode"] ?? 'light';
        return in_array($mode, ['light', 'dark'], TRUE) ? $mode : 'light';
    }

    public function setColorMode(string $mode) {
//$parsed = parse_url();
        $date = (new DateTime('+1 year'))->getTimestamp();
        setcookie("color_mode", $mode, $date);
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url = preg_replace( '/'. ("colormode" ? '(\&|)'."colormode".'(\=(.*?)((?=&(?!amp\;))|$)|(.*?)\b)' : '(\?.*)').'/i' , '', $url);  
		header("location: $url");
    }
}