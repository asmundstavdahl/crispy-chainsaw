<?php

namespace AsmundStavdahl\Php2Js;

$window->addEventListener(
    'load',
    fn () =>
    $document
        ->getElementById('theDiv')
        ->addEventListener(
            'click',
            fn ($e) => $console->log($e->target['id'])
        )
);

$window->addEventListener(
    "load",
    fn () =>
    $document
        ->getElementById("theDiv")
        ->innerHTML = "Hello, " . $person->getName()
);

$arr1 = [
    "A",
    "B",
    3.14,
    4,
    null,
];

$console->log($arr1);

class Person
{
    private string $name;

    static $anyValue = 'a';

    /**
     * Class constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

$person = new Person("Åsmund");

$console->log($person);
