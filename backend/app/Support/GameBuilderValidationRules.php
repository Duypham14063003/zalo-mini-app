<?php

namespace App\Support;

class GameBuilderValidationRules
{
    public static function gameTheme(): array
    {
        return [
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public static function gameFormField(): array
    {
        return [
            'field_key' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public static function prize(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'integer', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
