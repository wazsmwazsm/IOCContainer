# Container

## 介绍

一个简单好用的 IOC 容器。

IOC 容器是一个特殊的工厂类，它可以帮助你获取你需要的对象，并自动帮你注入需要的依赖 (包括依赖的依赖注入，一直到没有依赖为止)。

IOC 容器提供了单例操作、获取实例、执行实例方法 (给方法注入依赖) 等功能，你可以使用 IOC 容器解决多重依赖的问题，提高开发的效率。


## 获取实例

Container 提供了 getInstance 方法来获取一个实例，由 getInstance 方法获取的实例会自动进行依赖注入。

如果注入的实例也有依赖的话，IOC 容器会继续向下查找依赖进行依赖注入，直到所有的依赖注入完成。

例如：

```php
use IOC\Container;

class Foo
{
    public $a = 1;
    public $b = 2;
}

class Foz
{
    public $a = 5;
    public $b = 6;
    public function __construct(Foo $foo)
    {
        $this->a = $foo->a + $this->a;
        $this->b = $foo->b + $this->b;
    }
}

class Bar
{
    public $a = 0;
    public $b = 0;
    public function __construct(Foz $foz)
    {
        $this->a = $foz->a;
        $this->b = $foz->b;
    }
}

// 获取 Bar 的实例，Container 会自动进行依赖注入
$bar = Container::getInstance(Bar::class);
var_dump($bar->a); // 6
var_dump($bar->b); // 8
```

Container 还提供了获取实例单例的版本 getInstanceWithSingleton，如果要获取的实例没有设置单例，getInstanceWithSingleton 方法会将该实例设置为单例并返回该实例。


## 运行方法

Container 的 run 方法用来运行一个实例的方法，并且如果该方法有依赖则进行依赖注入。

例子：
```php
use IOC\Container;

class Foo
{
    public $a = 1;
    public $b = 2;
}

class Bar
{
    public function f1(Foo $foo)
    {
        return $foo->a + $foo->b;
    }
}

$result = Container::run(Bar::class, 'f1'); // result is 3
```

你还可以使用 run 方法的第三个参数给要运行的方法传入额外参数：

```php
use IOC\Container;

class Foo
{
    public $a = 1;
    public $b = 2;
}

class Bar
{
	// 注意额外传入的参数应该在要注入的参数之后
    public function f1(Foo $foo, $c, $d)
    {
        return $foo->a + $foo->b + $c + $d;
    }
}

$result = Container::run(Bar::class, 'f1', [4, 2]); // result is 9
```

## 单例

设置单例: singleton 方法
```php
use IOC\Container;
...
$a = new A();

Container::singleton($a);
```
获取单例: getSingleton 方法

```php
use IOC\Container;

// 传入要获取单例的类名
$a = Container::getSingleton('A');
```
设置单例时还可以指定名称：
```php
use IOC\Container;

$a = new A();
// 设置单例，指定名称
Container::singleton($a, 'my_singleton');
// 获取单例
$a = Container::getSingleton('my_singleton');
```

销毁单例：unsetSingleton 方法
```php
use IOC\Container;

// 销毁类 A 的单例
Container::unsetSingleton('A');
// 再次获取为 null
Container::getSingleton('A');
```

## 单例注册

Container 提供了一个 register 方法用来注册单例。和 singleton 方法不同的是，register 方法可以实现自定义类替换抽象类的功能。这个功能可以让你更改实例时不用重写获取实例的代码。

如下所示，注册一个 Exceptions\ExceptionHandler 单例，实际注册的实例是 App\Exceptions\Handler，获取该实例时可以通过 Exceptions\ExceptionHandler::class 来获取。
```php
use IOC\Container;

// set exception handler
Container::register(
    Exceptions\ExceptionHandler::class, 
    App\Exceptions\Handler::class
);

// get singleton 这里获取的其实是 App\Exceptions\Handler 的实例
$handler = Container::getSingleton(Exceptions\ExceptionHandler::class);

```

当 register 方法的第二个参数不传时，默认使用抽象类的实例。
```php
use IOC\Container;

// set exception handler
Container::register(Exceptions\ExceptionHandler::class);

// get singleton 这里获取的是 Exceptions\ExceptionHandler 的实例
$handler = Container::getSingleton(Exceptions\ExceptionHandler::class);

```