<?php

return [

    'required'   => 'El campo :attribute es obligatorio.',
    'string'     => 'El campo :attribute debe ser texto.',
    'email'      => 'El campo :attribute debe ser una dirección de correo válida.',
    'max'        => [
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'min'        => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'unique'     => 'El valor del campo :attribute ya está registrado.',
    'confirmed'  => 'La confirmación del campo :attribute no coincide.',
    'boolean'    => 'El campo :attribute debe ser verdadero o falso.',

    'attributes' => [
        'name'                  => 'nombre',
        'username'              => 'usuario',
        'email'                 => 'correo electrónico',
        'password'              => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'document_number'       => 'número de documento',
        'first_name'            => 'nombres',
        'last_name'             => 'apellidos',
    ],

];
