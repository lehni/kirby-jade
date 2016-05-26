<?php

namespace Tale\Config;

interface FormatInterface
{

    public function load($path);
    //TODO: public function save($path, array $options)
}