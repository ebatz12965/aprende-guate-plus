<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

/**
 * Protección CSRF de Laravel sin la cookie XSRF-TOKEN.
 *
 * Esa cookie existe para que bibliotecas como axios lean el token desde JavaScript, por eso no puede ser httponly.
 * La plataforma no usa axios: los formularios llevan el campo _token y las peticiones fetch envían X-CSRF-TOKEN
 * desde la etiqueta <meta>. Sin la cookie no queda ningún token legible fuera de la página.
 */
class PreventRequestForgery extends Middleware
{
    protected $addHttpCookie = false;
}
