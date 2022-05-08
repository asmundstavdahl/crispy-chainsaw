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

    /**
     * Class constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

$console->log(new Person("Åsmund"));
