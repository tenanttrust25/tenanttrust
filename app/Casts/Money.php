<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Money implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return (float) $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return number_format((float) $value, 2, ".", "");
    }
}
