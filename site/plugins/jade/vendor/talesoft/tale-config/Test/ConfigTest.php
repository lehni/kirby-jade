<?php

namespace Tale\Test;

use Tale\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    public function testLoading()
    {

        $expected = [
            'db'     => [
                'host'     => 'localhost',
                'password' => '12345',
            ],
            'models' => 'a',
            'a'      => [
                'b' => [
                    'c' => [
                        'd' => 'ABCD',
                    ],
                ],
            ],
        ];

        //$this->assertEquals($expected, Config::load(__DIR__.'/config/common.xml'), 'XML Config');
        //$this->assertEquals($expected, Config::load(__DIR__.'/config/common.yml'), 'YAML Config');
        $this->assertEquals($expected, Config::load(__DIR__.'/config/common.json'), 'JSON Config');
        $this->assertEquals($expected, Config::load(__DIR__.'/config/common.php'), 'PHP Config');
        $this->assertEquals($expected, Config::load(__DIR__.'/config/common.ini'), 'INI Config');
        $this->assertEquals($expected, Config::load(__DIR__.'/config/common'), 'Auto-Detected');
    }

    public function testLoadOptional()
    {

        $this->assertEquals([], Config::load('non-existent.file', true));
    }
}