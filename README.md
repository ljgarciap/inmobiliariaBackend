<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Consideraciones previas

## Instalar Dependencias

composer install

Tener en cuenta que se está usando passport para la autenticación; por ello hay que pasar por el siguiente proceso:

## Instalar Passport

php artisan passport:install

## Generar las claves de encriptación

php artisan passport:keys

## Ejecutar migraciones

php artisan migrate

## Ejecutar seeders

php artisan db:seed

### Crear directorio para imágenes

mkdir -p storage/app/public/propiedades

### Crear enlace simbólico

php artisan storage:link
