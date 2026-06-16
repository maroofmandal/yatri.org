<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_');
    }
}
