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
            'cedulafrente'=>'required',
            'ceduladorso'=>'required',
            'nacimiento'=>'required',
            'celular'=>'required',
            'messages'=>[
                'cedula.required'=>'La cédula es requerida.',
                'cedula.unique'=>'La cédula ya existe',
                'email.unique'=>'Email ya existe',
                'email.email'=>'Email debe tener formato ejemplo@ejemplo.com',
                ]
            ]
        ],
    "verify"=>[
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
