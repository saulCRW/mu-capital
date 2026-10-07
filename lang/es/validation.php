<?php

/*
 * Mensajes de validación en español para las reglas que usa el sistema.
 * Las reglas que no estén aquí usan el texto en inglés de Laravel (fallback).
 */
return [
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'decimal' => ':attribute debe tener :decimal decimales.',
    'email' => ':attribute debe ser un correo válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
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
    'password' => [
        'letters' => ':attribute debe contener al menos una letra.',
        'mixed' => ':attribute debe contener mayúsculas y minúsculas.',
        'numbers' => ':attribute debe contener al menos un número.',
        'symbols' => ':attribute debe contener al menos un símbolo.',
        'uncompromised' => ':attribute apareció en una filtración de datos. Elige otra.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => ':attribute debe ser texto.',
    'unique' => 'Ese :attribute ya está registrado.',

    'attributes' => [
        'email' => 'correo',
        'password' => 'contraseña',
        'nombre' => 'nombre',
        'rol' => 'rol',
        'estado' => 'estado',
    ],
];
