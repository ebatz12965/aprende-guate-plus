<x-mail::message>
# ¡El correo saliente funciona!

Si estás leyendo esto, Aprende Guate ya puede enviar correos: verificación de cuentas, restablecimiento de contraseñas y avisos de la plataforma.

<x-mail::panel>
**Servidor de correo:** {{ $host }}<br>
**Enviado por:** {{ $sentBy }}<br>
**Fecha:** {{ $sentAt }}
</x-mail::panel>

Si este correo llegó a la carpeta de spam, revisa los registros SPF y DKIM de tu dominio.

<x-mail::button :url="config('app.url')">
Ir a Aprende Guate
</x-mail::button>

Saludos,<br>
El equipo de Aprende Guate
</x-mail::message>
