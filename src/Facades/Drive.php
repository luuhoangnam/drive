<?php

namespace Namest\Drive\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Drive
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive\Facades
 *
 */
class Drive extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'drive';
    }

}
