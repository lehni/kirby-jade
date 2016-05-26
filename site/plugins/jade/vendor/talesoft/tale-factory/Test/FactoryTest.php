<?php

namespace Tale\Test;

use Tale\Factory;
use Tale\Factory\SingletonFactory;
use Tale\FactoryException;

interface I {}

class A implements I {}
class B implements I {}
class C {}
class D extends A {}

class FactoryTest extends \PHPUnit_Framework_TestCase
{


    public function testPragmatic()
    {

        $factory = new Factory(I::class, ['a' => A::class, 'c' => C::class, 'd' => D::class]);

        $this->assertInstanceOf(A::class, $factory->create('a'));
        $this->assertInstanceOf(B::class, $factory->create(B::class));
        $this->assertInstanceOf(D::class, $factory->create('d'));

        $this->setExpectedException(FactoryException::class);
        $c = $factory->create('c');
    }

    public function testSingletonFactory()
    {

        $factory = new SingletonFactory(I::class);

        $a1 = $factory->get(A::class);
        $a2 = $factory->get(A::class);

        $this->assertSame($a1, $a2);

        $this->setExpectedException(FactoryException::class);
        $c = $factory->get(C::class);
    }

}