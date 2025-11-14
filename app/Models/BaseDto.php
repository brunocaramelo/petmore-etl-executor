<?php

namespace App\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;

class BaseDto implements Arrayable, Jsonable
{
    protected $attributes = [];
    protected $casts = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        if (isset($this->casts[$key])) {
            $type = $this->casts[$key];
            $value = $this->castValue($value, $type);
        }

        $this->attributes[$key] = $value;
    }

    protected function castValue($value, $type)
    {
        return match($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => (array) $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
