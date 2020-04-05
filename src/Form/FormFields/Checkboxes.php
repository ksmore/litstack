<?php

namespace Fjord\Form\FormFields;

class Checkboxes
{
    const TRANSLATABLE = false;

    const REQUIRED = [
        'type',
        'id',
        'title',
        'options'
    ];

    const DEFAULTS = [
        'readonly' => false,
    ];
}
