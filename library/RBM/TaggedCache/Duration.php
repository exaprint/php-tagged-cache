<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 13/12/2013
 * Time: 19:02
 */

namespace RBM\TaggedCache;


class Duration
{

    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const MONTH = 2592000;
    const YEAR = 31536000;

    public static function get($howMany, $seconds){
        return $howMany * $seconds;
    }
} 