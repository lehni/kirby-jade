<?php

namespace Tale\Config\Format;

use Tale\Config\FormatInterface;

class Php implements FormatInterface
{

    public function load($path)
    {

        return include($path);
    }
}