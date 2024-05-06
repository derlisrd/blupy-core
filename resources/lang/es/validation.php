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
        ]
    ],
];
