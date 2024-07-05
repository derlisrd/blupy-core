<?php

return [
    'cambio'=>[
        'email'=>[
            'email'=>'required|email',
            'password'=>'required',
            'messages'=>[
                'email.required'=>'Email nuevo es obligatorio.',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
                'password.required'=>'Contraseña es obligatoria.'
            ]
        ],
        'telefono'=>[
            'telefono'=>'required',
            'password'=>'required',
            'messages'=>[
                'telefono.required'=>'Telefono es obligatorio.',
                'password.required'=>'Contraseña es obligatoria.'
            ]
        ],
    ],
    'verificaciones'=>[
        'email'=>[
            'email'=>'required|email',
            'messages'=>[
                'email.required'=>'Email es obligatorio.',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
            ]
        ],
        'confirmar'=>[
            'id'=>'required|numeric',
            'codigo'=>'required',
            'messages'=>[
                'id.required'=>'El id es obligatorio.',
                'id.numeric'=>'El id debe ser numerico.',
                'codigo.required'=>'Codigo es obligatorio.'
            ],
        ],
        'sms'=>[
            'telefono'=>'required',
            'messages'=>[
                'telefono.required'=>'Telefono es obligatorio.',
            ]
        ],
    ],
    'rest'=>[
        'login'=>[
            'email'=>'required|email',
            'password'=>'required',
            'messages'=>[
                'email.required'=>'Email es obligatorio.',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
                'password.required'=>'Contraseña es obligatoria.'
            ],
        ],
    ],
    "user"=>[
        "newpassword"=>[
            'old_password'=>'required',
            'password'=>'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
            'messages'=>[
                'old_password'=>'Contraseña anterior es obligatoria.',
                'password.min'=>'Contraseña debe tener al menos 8 caracteres.',
                'password.required'=>'Contraseña es obligatorio.',
                'password.confirmed'=>'La confirmación y contraseña debe ser iguales',
                'password_confirmation.required'=>'Confirmación de contraseña es obligatorio.',
            ]
        ],
        "resetpassword"=>[
            'password'=>'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
            'token'=>'required',
            'messages'=>[
                'password.min'=>'Contraseña debe tener al menos 8 caracteres.',
                'password.required'=>'Contraseña es obligatorio.',
                'password.confirmed'=>'La confirmación y contraseña debe ser iguales',
                'password_confirmation.required'=>'Confirmación de contraseña es obligatorio.',
                'token.required'=>'Token es obligatorio'
            ]
        ],
    ],
    "auth"=>[
        "login"=>[
            "cedula"=> 'required',
            'password'=>'required',
            'messages'=>[
                'cedula.required'=>'Cédula es requerida.',
                "password.required"=>"Contraseña es requerida."
                ]
            ],
        "register"=>[
            'nombres' => 'required',
            'apellidos' => 'required',
            'cedula'=>'required|unique:clientes,cedula',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'fotocedulafrente'=>'required',
            'fotoceduladorso'=>'required',
            'fecha_nacimiento' => 'required|date_format:Y-m-d',
            'celular'=>'required',
            'messages'=>[
                'nombres.required'=>'Los nombres son requeridos.',
                'apellidos.required'=>'Los apellidos son requeridos.',
                'apellidos.required'=>'La cédula es requerida.',
                'cedula.unique'=>'La cédula ya existe',
                'email.required'=>'Email es requerido',
                'email.unique'=>'Email ya existe',
                'email.email'=>'Email debe tener formato ejemplo@ejemplo.com',
                'fotocedulafrente.required'=>'La foto de cedula(frente) es requerida.',
                'fotoceduladorso.required'=>'La Foto de cedula(dorso) es requerida.',
                'password.required'=>'Contraseña es requerida.',
                'password.min'=>'Contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
                'fecha_nacimiento.required'=>'La fecha_nacimiento es requerida.',
                'fecha_nacimiento.required' => 'El campo fecha de nacimiento es obligatorio.',
                'fecha_nacimiento.date_format' => 'El campo fecha de nacimiento debe tener el formato YYYY-MM-DD.',
                'celular.required'=>'Celular es requerido.',
                ]
            ]
        ],
    "verify"=>[
        "olvide"=>[
            'cedula'=>'required',
            'forma'=>'required|numeric',
            'messages'=>[
                'cedula.required'=>'La cedula es requerida',
                'forma.required'=>'La forma es requerida',
                'forma.numeric'=>'La forma es debe ser 0 o 1',
            ]
        ],
        "codigo"=>[
            'id'=>'required',
            'codigo'=>'required',
            'messages'=>[
                'id.required'=>'El id es obligatorio',
                'codigo.required'=>'El código es obligatorio'
            ]
        ],
        "documento"=>[
            'cedula'=>'required',
            'messages'=>[
                'cedula.required'=>'La cedula es obligatoria'
            ],
        ],
        "scan"=>[
            'fotofrontal64'=>'required',
            'fotodorsal64'=>'required',
            'cedula'=>'required',
            'nombres'=>'required',
            'apellidos'=>'required',
            'nacimiento'=>'required',
            'messages'=>[
                'fotofrontal64.required' =>'La foto frontal en base64 es requerida.',
                'fotodorsal64.required' =>'La foto dorsal en base64 es requerida.',
                'cedula.required'=>'La cédula es requerida.',
                'nombres.required'=>'El nombre es requerido.',
                'apellidos.required'=>'El apellido es requerido.',
                'nacimiento.required'=>'La fecha de nacimiento es requerida.',
            ]
        ]
    ],
    "solicitudes"=>[
        "listar"=>[
            'fechaDesde'=>'required',
            'fechaHasta'=>'required',
            'messages'=>[
                'fechaDesde.required'=>'La fechaDesde es requerida (YYYY-MM-DD)',
                'fechaHasta.required'=>'La fechaHasta es requerida (YYYY-MM-DD)',
            ]
        ],
        "solicitar"=>[
            'latitud_direccion'=>'required',
            'longitud_direccion'=>'required',
            'departamento_id'=>'required',
            'ciudad_id'=>'required',
            'barrio_id'=>'required',
            'calle'=>'required',
            'referencia_direccion'=>'required',
            'profesion_id'=>'required',
            'salario'=>'required|numeric',
            'antiguedad_laboral'=>'required|numeric|min:0',
            'antiguedad_laboral_mes'=>'required|numeric|min:1|max:11',
            'empresa'=>'required',
            'empresa_telefono'=>'required',
            'empresa_direccion'=>'required',
            'empresa_departamento_id'=>'required',
            'empresa_ciudad_id'=>'required',
            'tipo_empresa_id'=>'required',
            'messages'=>[
                'latitud_direccion.required'=>'Latitud es obligatorio.',
                'longitud_direccion.required'=>'Longitud es obligatorio.',
                'departamento_id.required'=>'Departamento es requerido.',
                'ciudad_id.required'=>'Ciudad es oligatorio',
                'barrio_id.required'=>'Barrio es obligatorio',
                'calle.required'=>'Dirección de calle es obligatorio',
                'referencia_direccion.required'=>'Referencia es obligatorio',
                'profesion_id.required'=>'Profesión es obligatorio.',
                'salario.required'=>'Salario es obligatorio',
                'salario.numeric'=>'Salario debe ser número',
                'antiguedad_laboral.required'=>'Antigüedad en año es obligatorio.',
                'antiguedad_laboral.min'=>'Antigüedad en año debe ser mínimo de 0',
                'antiguedad_laboral.numeric'=>'Antigüedad en año debe ser un número.',
                'antiguedad_laboral_mes.required' => 'Antigüedad en meses es obligatorio.',
                'antiguedad_laboral_mes.numeric' => 'Antigüedad en meses debe ser un número.',
                'antiguedad_laboral_mes.min' => 'Antigüedad en meses debe estar entre 1 y 11.',
                'antiguedad_laboral_mes.max' => 'Antigüedad en meses debe estar entre 1 y 11.',
                'empresa'=>'Nombre de empresa es obligatorio.',
                'empresa_direccion.required'=>'Dirección de empresa es obligatorio.',
                'empresa_telefono.required'=>'Telefono de empresa es obligatorio.',
                'empresa_departamento_id.required'=>'Departamento de la empresa es obligatorio.',
                'empresa_ciudad_id.required'=>'Ciudad de la empresa es obligatorio.',
                'tipo_empresa_id.required'=>'Tipo de empresa es obligatorio.'
            ]
        ],
    ],
];
