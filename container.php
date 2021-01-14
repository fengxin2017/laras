<?php
Class Foo{
}
Class Bar{
    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }
}

Class Baz{
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

$baz = new Baz(new Bar(new Foo()));

Class Container{
    protected $bindings = [];
    protected $instances = [];

    private function __construct()
    {

    }
    public static function getInstance()
    {
        return new static();
    }

    public function bind($abstract,Closure $closure)
    {
        $this->bindings[$abstract] = $closure;
    }

    public function instance($abstract,$instance)
    {
        $this->instances[$abstract] = $instance;
    }
    public function make($abstract,$parameters = [])
    {
        if(isset($this->instances[$abstract])){
            return $this->instances[$abstract];
        }
        if(isset($this->bindings[$abstract])){
            return call_user_func($this->bindings[$abstract]);
        }

        $reflectionClass = new ReflectionClass($abstract);

        $constructor = $reflectionClass->getConstructor();
        if(is_null($constructor)){
            return new $abstract;
        }else{
            $params = [];
            $reflectionParams = $constructor->getParameters();
            foreach ($reflectionParams as $reflectionParam){
                if(isset($parameters[$reflectionParam->name])){
                    $params[] = $parameters[$reflectionParam->name];
                }else{
                    if($reflectionParam->isDefaultValueAvailable()){
                        $params[] = $reflectionParam->getDefaultValue();
                    }else{
                        $params[] = $this->make($reflectionParam->getType()->getName());
                    }
                }
            }
            return $reflectionClass->newInstanceArgs($params);
        }
    }
}

$container = Container::getInstance();


var_dump($container->make(Baz::class));

