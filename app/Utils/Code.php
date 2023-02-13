<?php

namespace App\Utils;

class Code
{
    public function generate(): int
    {
        return random_int(100000, 999999);
    }
}
