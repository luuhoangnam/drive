<?php

namespace Namest\Drive\Contracts;

/**
 * Interface Drive
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive\Contracts
 *
 */
interface Drive
{
    /**
     * @param string $filename
     *
     * @return string
     */
    public function store($filename);
}
