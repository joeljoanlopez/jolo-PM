<?php

return [
    // De quién se acepta X-Forwarded-For (y X-Forwarded-Proto) para saber la IP
    // real del cliente, que es la que cuenta en el límite de intentos del login.
    //   - vacío: de nadie; la IP es la de quien conecta.
    //   - `REMOTE_ADDR`: solo de quien hace la petición. En Docker, el nginx del
    //     contenedor `frontend` pasa a PHP como REMOTE_ADDR a quien le conecta,
    //     que es tu proxy inverso (el puerto solo escucha en 127.0.0.1).
    //   - IPs o rangos separados por comas: solo de esos.
    // `*` NO sirve: en Laravel confía en cualquier IP, y entonces el cliente
    // podría inventarse su IP con la cabecera. Ver TRUSTED_PROXIES en el README.
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
