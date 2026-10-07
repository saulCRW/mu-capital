<?php

/*
 * Mensajes de validación en español para las reglas que usa el sistema.
 * Las reglas que no estén aquí usan el texto en inglés de Laravel (fallback).
 */
return [
    'before_or_equal' => ':attribute debe ser una fecha igual o anterior a :date.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date_format' => ':attribute debe tener el formato :format.',
    'decimal' => ':attribute debe tener :decimal decimales.',
    'email' => ':attribute debe ser un correo válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'gt' => [
        'numeric' => ':attribute debe ser mayor que :value.',
    ],
    'in' => 'El :attribute seleccionado no es válido.',
    'integer' => ':attribute debe ser un número entero.',
    'max' => [
        'numeric' => ':attribute no debe ser mayor que :max.',
        'string' => ':attribute no debe tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => ':attribute debe ser al menos :min.',
        'string' => ':attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => ':attribute debe ser un número.',
    'password' => [
        'letters' => ':attribute debe contener al menos una letra.',
        'mixed' => ':attribute debe contener mayúsculas y minúsculas.',
        'numbers' => ':attribute debe contener al menos un número.',
        'symbols' => ':attribute debe contener al menos un símbolo.',
        'uncompromised' => ':attribute apareció en una filtración de datos. Elige otra.',
    ],
    'prohibited' => 'El campo :attribute no está permitido.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => ':attribute debe ser texto.',
    'unique' => 'Ese :attribute ya está registrado.',

    'attributes' => [
        'email' => 'correo',
        'password' => 'contraseña',
        'nombre' => 'nombre',
        'rol' => 'rol',
        'estado' => 'estado',
        'contacto' => 'contacto',
        'perfil_riesgo' => 'perfil de riesgo',
        'asesor_id' => 'asesor',
        'moneda_base' => 'moneda base',
        'benchmark_id' => 'benchmark',
        'tipo' => 'tipo',
        'fecha' => 'fecha',
        'asset_id' => 'activo',
        'cantidad' => 'cantidad',
        'precio' => 'precio',
        'comision' => 'comisión',
        'moneda' => 'moneda',
        'tipo_cambio' => 'tipo de cambio',
        'nota' => 'nota',
        'simbolo' => 'símbolo',
        'sector' => 'sector',
        'pais' => 'país',
        'exchange' => 'exchange',
    ],
];
