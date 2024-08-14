<?php

return [
    'bancard'=>[
        'consultarDeuda'=>[
            'cedula' => 'required',
            'cuenta' => 'required|numeric',
            'messages'=>[
                'cedula.required'=>'Cédula es obligatoria.',
                'cuenta.required'=>'Cuenta es obligatoria.',
                'cuenta.numeric'=>'Cuenta debe ser un número.',
            ],
        ],
        'pagarDeuda'=>[
            'cedula' => 'required',
            'cuenta' => 'required|numeric',
            'importe' => 'required|numeric',
            'messages'=>[
                'cedula.required'=>'Cédula es obligatoria.',
                'cuenta.required'=>'Cuenta es obligatoria.',
                'cuenta.numeric'=>'Cuenta debe ser un número.',
                'importe.required'=>'Importe es obligatorio.',
                'importe.numeric'=>'Importe debe ser un número.',
            ],
        ]
    ],
    'extracto'=>[
        'periodo'=>['nullable', 'regex:/^\d{2}-\d{4}$/'],
        'cuenta'=>'numeric',
        'messages'=>[
            'periodo.regex'=>'El periodo es debe ser (MM-AAAA).',
            'cuenta.numeric'=>'La cuenta debe ser número'
        ]
    ],
    'movimientos'=>[
        'periodo'=>['nullable', 'regex:/^\d{2}-\d{4}$/'],
        'cuenta'=>'nullable|numeric',
        'messages'=>[
            'periodo.regex'=>'El periodo es debe ser (MM-AAAA).',
            'cuenta.numeric'=>'La cuenta debe ser número'
        ]
    ],
    'device'=>[
        'codigo'=>[
            'email' => 'required|email',
            'messages'=>[
                'email.required'=>'Email nuevo es obligatorio.',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
            ],
        ],
        'confirmar'=>[
            "codigo"=> 'required',
            'id'=>'required',
            'desktop'=>'required|boolean',
            'confianza'=>'boolean',
            'web'=>'required|boolean',
            'device'=>'required',
            'messages'=>[
                'codigo.required'=>'Codigo es obligatorio.',
                "id.required"=>"Id es obligatorio.",
                "desktop.required"=>"Tipo de dispositivo es obligatorio. (desktop)",
                "desktop.boolean"=>"Tipo de dispositivo es boleano.",
                "web.required"=>"SistemaOS de ingreso es obligatorio. (web)",
                "web.boolean"=>"SistemaOS de ingreso es boleano.",
                "device.required"=>"Dispositivo es obligatorio (device)",
                "confianza.boolean"=>"Confianza de ingreso es boleano.",
                ]
        ],
    ],
    'cambio'=>[
        'email'=>[
            'email' => 'required|email|unique:users,email',
            'password'=>'required',
            'messages'=>[
                'email.required'=>'Email nuevo es obligatorio.',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
                'email.unique'=>'Este email ya esta en uso',
                'password.required'=>'Contraseña es obligatoria.'
            ]
        ],
        'celular'=>[
            'celular'=>'required|regex:/^[0-9]{10}$/|unique:clientes,celular',
            'password'=>'required',
            'messages'=>[
                'celular.required'=>'Numero de celular es obligatorio.',
                'celular.unique'=>'Ese numero no esta disponible.',
                'celular.regex'=>'Debe tener formato de numero de celular',
                'password.required'=>'Contraseña es obligatoria.'
            ]
        ],
    ],
    'verificaciones'=>[
        'email'=>[
            'email'=>'required|email|unique:users,email',
            'messages'=>[
                'email.required'=>'Email es obligatorio.',
                'email.unique'=>'Este email ya esta en uso',
                'email.email'=>'Email debe tener formato ejemplo@email.com',
            ]
        ],
        'confirmar'=>[
            'codigo'=>'required',
            'messages'=>[
                'codigo.required'=>'Codigo es obligatorio.'
            ],
        ],
        'celular'=>[
            'celular'=>'required|regex:/^[0-9]{10}$/|unique:clientes,celular',
            'messages'=>[
                'celular.required'=>'Numero de celular es obligatorio.',
                'celular.unique'=>'Ese numero no esta disponible.',
                'celular.regex'=>'Debe tener formato de numero de celular',
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
            'desktop'=>'required|boolean',
            'web'=>'required|boolean',
            'device'=>'required',
            'messages'=>[
                'cedula.required'=>'Cédula es requerida.',
                "password.required"=>"Contraseña es requerida.",
                "desktop.required"=>"Tipo de dispositivo es obligatorio. (desktop)",
                "desktop.boolean"=>"Tipo de dispositivo es boleano.",
                "web.required"=>"SistemaOS de ingreso es obligatorio. (web)",
                "web.boolean"=>"SistemaOS de ingreso es boleano.",
                "device.required"=>"Dispositivo es obligatorio (device)"
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
            'desktop'=>'required|boolean',
            'web'=>'required|boolean',
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
                'desktop.required'=>'Tipo de dispositivo es obligatorio (desktop)',
                'desktop.boolean'=>'Tipo de dispositivo es verdadero o falso',
                'web.required'=>'SistemaOS es obligatorio (web)',
                'web.boolean'=>'SistemaOS es verdadero o falso',
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
        "email"=>[
            'email'=>'required,email',
            'messages'=>[
                'email.required'=>'Email es obligatorio.',
                'email.email'=>'Email debe tener formato correcto (usuario@email.com)'
            ],
        ],
        "telefono"=>[
            'celular'=>'required',
            'messages'=>[
                'celular.required'=>'Celular es obligatorio.'
            ],
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
            'fechaDesde'=>'nullable|date_format:Y-m-d',
            'fechaHasta'=>'nullable|date_format:Y-m-d',
            'messages'=>[
                'fechaDesde.date_format'=>'La fechaDesde debe tener formato (YYYY-MM-DD)',
                'fechaHasta.date_format'=>'La fechaHasta debe tener formato (YYYY-MM-DD)',
            ]
        ],
        'adicional'=>[
            'cedula'=>'required|unique:adicionales,cedula',
            'nombres'=>'required',
            'apellidos'=>'required',
            'limite'=>'required|numeric|min:0',
            'direccion'=>'required',
            'celular'=>'required',
            'maectaid'=>'required|numeric',
            'messages'=>[
                'cedula.unique'=>'Cedula no disponible.',
                'cedula.required'=>'La cedula es obligatoria.',
                'nombres.required'=>'Nombres es obligatorio',
                'apellidos.required'=>'Apellidos es obligatorio',
                'limite.required'=>'Limite es obligatorio',
                'limite.min'=>'El limite es de un minimo de 0',
                'limite.numeric'=>'El limite debe ser numero.',
                'direccion.required'=>'La direccion es obligatoria.',
                'celular.required'=>'Celular es obligatorio.',
                'maectaid.required'=>'Cuenta es obligatoria (maectaid)',
                'maectaid.numeric'=>'Cuenta debe ser numero (maectaid)',
             ]
        ],
        'ampliacion'=>[
            'numeroCuenta' => 'required',
            'lineaSolicitada' => 'required',
            'fotoIngreso' => 'required',
            'fotoAnde'=>'required',
            'messages'=>[
                'numeroCuenta.required'=>'Numero cuenta es obligatorio (numeroCuenta)',
                'lineaSolicitada.required'=>'Linea es obligatoria (lineaSolicitada)',
                'fotoIngreso.required'=>'Foto de ingreso es obligatoria (fotoIngreso)',
                'fotoAnde.required'=>'Foto de factura de ande es obligatoria (fotoAnde)'
            ],
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
