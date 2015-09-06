<?php
/**
 * Created by PhpStorm.
 * User: nick
 * Date: 6/18/15
 * Time: 5:10 PM
 */

namespace IFR\Main\Patterns;


trait Singleton
{
    static private $_instance = null;

    /**
     * @return static
     */
    static public function get()
    {
        if(null == self::$_instance)
        {
            $instance = new static();

            self::$_instance = $instance;
        }

        return self::$_instance;
    }
}