# Web-server
# Projecte Laravel
## Requisits previs
- Composer
- php 8.2
- .env, cambiar variables per apuntar al servidor de base de dades

## Instal·lació
1. Accediu a la carpeta `sources/Web-server/server`
2. Executeu la instal·lació de dependències:
```bash
composer install
```
3. Executeu la transferencia de dades de la base de dades, (si fa preguntas posa totes les opcions a yes):
```bash
php artisan migrate --seed
```
4. executa la comanda per iniciar el servidor
```bash
php artisan serve:full
```
