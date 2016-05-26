<?php

namespace Tale\Test;

use Tale\Config\DelegateTrait;
use Tale\ConfigurableInterface;
use Tale\ConfigurableTrait;

class DbConnection implements ConfigurableInterface
{
    use ConfigurableTrait;

    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'encoding' => 'utf-8'
        ], $options);
    }
}


class Container implements ConfigurableInterface
{
    use ConfigurableTrait;
}

class Delegate implements ConfigurableInterface
{
    use DelegateTrait;

    private $container;
    private $nameSpace;

    public function __construct(Container $container, $nameSpace = '')
    {

        $this->container = $container;
        $this->nameSpace = $nameSpace;
    }

    protected function getTargetConfigurableObject()
    {

        return $this->container;
    }

    protected function getOptionNameSpace()
    {

        return $this->nameSpace;
    }
}

class DbDelegate implements ConfigurableInterface
{
    use DelegateTrait;

    private $container;

    public function __construct(Container $container)
    {

        $this->container = $container;
    }

    protected function getTargetConfigurableObject()
    {

        return $this->container;
    }

    protected function getOptionNameSpace()
    {

        return 'db';
    }
}

class ConfigurableTraitTest extends \PHPUnit_Framework_TestCase
{

    public function testDefineOptions()
    {

        $db = new DbConnection(['password' => 'some password', 'host' => 'some host']);

        $this->assertEquals('some password', $db->getOption('password'));
        $this->assertEquals('some host', $db->getOption('host'));
    }

    public function testLoadOptions()
    {

        $db = new DbConnection();
        $db->loadOptions(__DIR__.'/config/db.json');

        $this->assertEquals('12345', $db->getOption('password'));
        $this->assertEquals('some-host:3306', $db->getOption('host'));
        $this->assertEquals('iso-8859-1', $db->getOption('encoding'));
    }

    public function testDelegateTrait()
    {

        $container = new Container();
        $container->loadOptions(__DIR__.'/config/common');

        $delegate = new Delegate($container);
        $this->assertEquals('localhost', $delegate->getOption('db.host'));
        $this->assertEquals('12345', $delegate->getOption('db.password'));

        $delegate = new Delegate($container, 'db');
        $this->assertEquals('localhost', $delegate->getOption('host'));
        $this->assertEquals('12345', $delegate->getOption('password'));

        $delegate = new DbDelegate($container);
        $this->assertEquals('localhost', $delegate->getOption('host'));
        $this->assertEquals('12345', $delegate->getOption('password'));
    }
}