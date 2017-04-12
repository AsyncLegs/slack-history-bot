<?php

namespace Terekhov\Bot;
use MongoDB\Client as Mongo;


class Persistence
{
    private $mongoClient;

    public function __construct($mongoClient)
    {
        $this->mongoClient = $mongoClient;
    }


}