<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Uso local sin Internet y traslado a un servidor

La aplicación usa `MEDIA_DISK=auto`: si hay credenciales completas del bucket,
intenta guardar allí; si faltan o la subida falla, guarda en disco local.
YouTube y los enlaces a GIFs o videos externos requieren Internet.
Los archivos guardados localmente siguen siendo locales aunque después se conecte un bucket.

Para preparar la imagen en un equipo con Internet:

```sh
docker compose build
docker save -o rutinas-app.tar rutinas-app:local
```

En la PC del cliente, con Docker instalado, copiar `rutinas-app.tar` y `compose.yaml`
a una carpeta y ejecutar:

```sh
docker load -i rutinas-app.tar
docker compose up -d --no-build --pull never
```

Abrir `http://localhost:8000`. El primer acceso es `admin` / `admin123`;
cambiar la contraseña desde la aplicación. Los reinicios conservan los cambios del administrador.
La imagen ya incluye las dependencias: el arranque no descarga paquetes.

Compose mantiene fuera del contenedor la carpeta `data/`, con:

- `database.sqlite`: usuarios, rutinas, biblioteca y configuración.
- `media/`: imágenes, videos y logos subidos sin bucket.
- `app.key`: clave estable de la aplicación.

Detener la aplicación con `docker compose stop` antes de copiar o respaldar `data/`.
Para trasladarla a un servidor, copiar la carpeta completa junto con `compose.yaml`
y la imagen; iniciarla con el mismo comando. Configurar `APP_URL` con el dominio
del servidor en un `.env` al lado de Compose. Si se necesita acceso directo desde
otros equipos, configurar `APP_BIND_ADDRESS=0.0.0.0`; por defecto escucha solo en la PC.
Si cambia el puerto, configurar también `APP_URL` con ese puerto.

Se puede agregar la configuración del bucket en ese `.env` (`AWS_ACCESS_KEY_ID`,
`AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT_URL_S3`, `AWS_REGION`, `GIFS_BUCKET`).
Las nuevas cargas intentarán usarlo; las anteriores seguirán leyéndose desde `data/media`.
Este traslado conserva SQLite: pasar a PostgreSQL requiere migrar los datos por separado.
No eliminar `data/`: recrear el contenedor no la borra.

Para una instalación existente, copiar los archivos de `storage/app/private/` a
`data/media/`, conservar su base SQLite como `data/database.sqlite` y conservar
su `APP_KEY`. Los alojamientos con disco efímero requieren un volumen persistente
montado en `/data` para utilizar esta alternativa local.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
