<?php

return [
    /*
     * Credenciales de Service Account de Firebase (JSON descargado desde
     * Firebase Console > Configuración del proyecto > Cuentas de servicio >
     * Generar nueva clave privada), codificadas en base64 en una sola
     * variable de entorno - nunca un archivo en el repo (ver .gitignore para
     * la carpeta storage/app/secrets/, que queda sin usar a propósito). Si
     * la variable no está seteada, FcmChannel loguea y no envía - no rompe
     * el resto del dispatcher (M-17 §3).
     */
    'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),
];
