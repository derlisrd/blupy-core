<?php

return [
    "auth"=>[
        "login"=>[
            "cedula"=> 'required',
            'password'=>'required',
            'messages'=>[
                'cedula.required'=>'La cédula es requerida.',
                "password.required"=>"La contraseña es requerida."
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
        ]
    ],
];
