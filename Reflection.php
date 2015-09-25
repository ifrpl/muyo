<?php

namespace IFR\Main;

class Reflection
{
    public static function getConstants($class, $prefix)
    {
        $r = new \ReflectionClass($class);

        $constants = $r->getConstants();

        $keys = array_filter(
            array_keys($constants),
            function ($key) use ($prefix)
            {
                return 0 === strpos($key, $prefix);
            }
        );

        return array_combine(
            $keys,
            array_join($keys, $constants)
        );

    }
}