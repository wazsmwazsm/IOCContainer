<?php
namespace WorkerF\Tests;

use PHPUnit_Framework_TestCase;
use IOC\Container;
use ReflectionClass;

class ContainerFake extends Container
{
    public static function getDiParams(array $params)
    {
        return self::_getDiParams($params);
    }
    public static function clean()
    {
        self::$_singleton = [];
    }
}

class Foo
{
    public $a = 1;
    public $b = 2;
}

class Foz
{
    public $a = 3;
    public $b = 4;
}

class Bar
{
    public $a = 2333;
    public $b = 666;

    public function __construct(Foo $foo, Foz $foz)
    {
        $this->a = $foo->a;
        $this->b = $foz->b;
    }

    public function f1(Foo $foo)
    {
        $this->a = $foo->a + $foo->b;
        return $this->a;
    }

    public function f2(Foo $foo, $id, $name)
    {
        $this->a = $foo->a + $foo->b;
        return 'Name: '.$name.' Id: '.$id.' Number: '.$this->a;
    }
}

class Fzz
{
    public $a = 5;
    public $b = 6;

    public function __construct(Foo $foo)
    {
        $this->a = $foo->a + $this->a;
        $this->b = $foo->b + $this->b;
    }
}

class brr
{
    public $a = 0;
    public $b = 0;
    public function __construct(Fzz $fzz)
    {
        $this->a = $fzz->a;
        $this->b = $fzz->b;
    }
}

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        ContainerFake::clean();
    }

    public function testSingleton()
    {
        $singleton = ContainerFake::getSingleton(Foo::class);
        $this->assertNull($singleton);
        // set singleton
        $foo = new Foo();
        ContainerFake::singleton($foo);
        $singleton = ContainerFake::getSingleton(Foo::class);
        $this->assertEquals($singleton, $foo);
        // unset singleton
        ContainerFake::unsetSingleton(Foo::class);
        $singleton = ContainerFake::getSingleton(Foo::class);
        $this->assertNull($singleton);
        // use name
        $foo = new Foo();
        ContainerFake::singleton($foo, 'foo');
        $singleton = ContainerFake::getSingleton('foo');
        $this->assertEquals($singleton, $foo);
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSingletonException()
    {
        ContainerFake::singleton(Foo::class);
    }

    public function testRegister()
    {
        // concrete is null
        ContainerFake::register(Foo::class);
        $this->assertEquals(new Foo, ContainerFake::getSingleton(Foo::class));
        
        // concrete is not null
        ContainerFake::register(Foz::class, Foo::class);
        $this->assertEquals(new Foo, ContainerFake::getSingleton(Foz::class));    
    }

    public function testGetDiParams()
    {
        // test construct
        $reflector = new ReflectionClass(Bar::class);
        $constructor = $reflector->getConstructor();
        $di_params = ContainerFake::getDiParams($constructor->getParameters());
        $this->assertEquals(2, count($di_params));
        $this->assertInstanceOf(Foo::class, $di_params[0]);
        $this->assertInstanceOf(Foz::class, $di_params[1]);
        // test function
        $reflector = new ReflectionClass(Bar::class);
        $reflectorMethod = $reflector->getMethod('f1');
        $di_params = ContainerFake::getDiParams($reflectorMethod->getParameters());
        $this->assertEquals(1, count($di_params));
        $this->assertInstanceOf(Foo::class, $di_params[0]);
    }

    public function testGetInstance()
    {
        // DI 
        $result = ContainerFake::getInstance(Bar::class);
        $this->assertEquals(1, $result->a);
        $this->assertEquals(4, $result->b);
        // DI nesting
        $result = ContainerFake::getInstance(Brr::class);
        $this->assertEquals(6, $result->a);
        $this->assertEquals(8, $result->b);
    }

    public function testGetInstanceSingleton()
    {
        $foo = new Foo();
        $foz = new Foz();
        $expect = new Bar($foo, $foz);
        $this->assertEquals(NULL, ContainerFake::getSingleton(Bar::class));
        // set singleton, set singleton
        $result = ContainerFake::getInstanceWithSingleton(Bar::class);
        $this->assertEquals($expect, ContainerFake::getSingleton(Bar::class));
    }

    public function testRun()
    {
        $foo = new Foo();
        $foz = new Foz();
        $expect = new Bar($foo, $foz);
        $result = ContainerFake::run(Bar::class, 'f1');
        
        $this->assertEquals($expect->f1($foo), $result);
    }

    public function testRunWithParam()
    {
        $foo = new Foo();
        $foz = new Foz();
        $expect = new Bar($foo, $foz);
        $result = ContainerFake::run(Bar::class, 'f2', [13, 'Jack']);
        
        $this->assertEquals($expect->f2($foo, 13, 'Jack'), $result);
    }

    /**
    * @expectedException \BadMethodCallException
    */
    public function testRunExceptionClassNotFound()
    {
        $result = ContainerFake::run(Baz::class, 'f1');
    }
    
    /**
    * @expectedException \BadMethodCallException
    */
    public function testRunExceptionMethodNotFound()
    {
        $result = ContainerFake::run(Bar::class, 'f3');
    }
}