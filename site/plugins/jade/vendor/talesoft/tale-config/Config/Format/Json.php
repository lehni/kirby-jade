<?php

namespace Tale\Config\Format;

use Tale\Config\FormatInterface;

class Json implements FormatInterface
{

    public function load($path)
    {

        return json_decode(file_get_contents($path), true);
    }
}