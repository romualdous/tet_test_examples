<?php

if (! function_exists('packString')) {
    /**
     * @param $value
     * @return string
     */
    function packString($value)
    {
        return pack("v", strlen($value)) . $value;
    }
}
