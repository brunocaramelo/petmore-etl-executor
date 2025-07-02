<?php

namespace App\Casts;

class ValueCast {

    public function castValue($value, $castType)
    {
        if ($castType =='integer'){
            return (integer) $value;
        }

        if ($castType =='decimal:2'){
            return (float) $value;
        }

        if ($castType =='float'){
            return (float) $value;
        }

        if ($castType =='boolean'){
            return (boolean) $value;
        }

    }

}