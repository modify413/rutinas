<?php

return [
    // Disco para archivos subidos (GIFs/videos/logo):
    // - neon: bucket Neon Object Storage (requiere internet + credenciales AWS)
    // - local: disco del servidor, servido por /media (funciona sin internet)
    'disk' => env('MEDIA_DISK', 'neon'),
];
