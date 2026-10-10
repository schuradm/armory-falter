<?php

namespace App\Enums;

trait HasLabel
{
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }
}
