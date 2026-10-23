<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk for media uploads
    |--------------------------------------------------------------------------
    |
    | Aquí se define en qué disco se guardan los archivos subidos (GIFs, videos, logos).
    | Valores posibles:
    |   - 'auto'    → Bucket configurado y disponible; si falla, disco local
    |   - 'neon'    → Intenta Neon y usa disco local si falla
    |   - 'local'   → Disco local del servidor (funciona sin internet)
    |
    */

    'disk' => env('MEDIA_DISK', 'auto'),

];
