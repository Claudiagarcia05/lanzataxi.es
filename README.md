<div align="center">
  <h1>LanzaTaxi</h1>
</div>

<div align="center">
  <img src="public/images/logo_sin_fondo.png" alt="LanzaTaxi" width="300" />

  <div>
    <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
    <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
    <img src="https://img.shields.io/badge/JavaScript-ESM-F7DF1E?style=for-the-badge&logo=javascript&logoColor=000" alt="JavaScript" />
    <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white" alt="Vue" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS" />
    <img src="https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite" />
    <img src="https://img.shields.io/badge/Node.js-18.x-339933?style=for-the-badge&logo=node.js&logoColor=white" alt="Node.js" />
    <img src="https://img.shields.io/badge/Composer-2.x-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer" />
    <img src="https://img.shields.io/badge/Git-F05032?style=for-the-badge&logo=git&logoColor=white" alt="Git" />
  </div>
</div>

---

## LanzaTaxi

LanzaTaxi es un sistema web diseñado para mejorar la gestión del servicio de taxi en Lanzarote. La idea es crear una herramienta que conecte de forma sencilla a pasajeros, conductores y administración, permitiendo hacer cosas como pedir un taxi, hacerle seguimiento al viaje, controlar los coches disponibles y llevar un registro de los pagos.

El proyecto nace para dar respuesta a una necesidad real de movilidad en una isla como Lanzarote. Tanto en la ciudad como en los pueblos, los residentes y visitantes necesitan desplazarse de forma rápida y sencilla. Por eso he tratado de que LanzaTaxi sea práctico, que funcione bien tanto en ordenador como en móvil, que sea fácil de mantener y que permita ir añadiendo funciones poco a poco.

El funcionamiento básico es bastante simple: un pasajero pide un taxi, el sistema recoge dónde está y qué necesita, un conductor acepta el servicio y lo realiza, y la administración puede consultar todo el proceso si hace falta. A partir de ahí se han añadido otros apartados para gestionar los taxis, los conductores, las direcciones y los pagos, además de un panel para que la administración tenga el control general.

Con LanzaTaxi los usuarios disfrutan de:
- **Digitalización completa** del proceso de pedir y gestionar viajes, más rápido y sin tener que estar llamando o apuntando cosas en papel.
- **Toda la información de la flota** en un solo sitio: taxis, conductores, viajes, pagos y direcciones.
- **Seguimiento de cada servicio**, guardando un historial fiable que se pueda consultar cuando haga falta.
- **Web fácil de usar**, que se entienda a simple vista y que funcione bien tanto en ordenador como en el móvil.
- **Sistema preparado para crecer**, pudiendo ir añadiendo cosas nuevas sin tener que empezar de cero.

---

## ¿Qué pueden hacer los diferentes usuarios en LanzaTaxi?

LanzaTaxi está diseñado para ofrecer una experiencia adaptada a las necesidades de pasajeros, conductores y administradores. Cada tipo de usuario tiene acceso a un conjunto específico de funcionalidades.

### Pasajero

Como pasajero, puedes solicitar un taxi de forma rápida y sencilla, sin necesidad de llamar por teléfono:

- **Solicitar un taxi** indicando dónde estás y a dónde quieres ir.
- **Seguimiento del viaje** una vez que un conductor ha aceptado el servicio.
- **Consulta del estado** del viaje (pendiente, aceptado, completado, etc.).
- **Registro de pagos**, pudiendo saber si están cobrados o pendientes.

### Conductor

Los conductores tienen acceso a las funcionalidades necesarias para gestionar los viajes que realizan:

- **Visualización de servicios disponibles** para aceptarlos.
- **Actualización del estado del taxi** (libre u ocupado) en tiempo real.
- **Gestión de los viajes aceptados**, con la información básica del trayecto.
- **Registro de los viajes realizados** y su estado.

### Administrador

Como administrador, tendrás acceso completo a todas las funcionalidades para gestionar el servicio de taxi de manera eficiente:

- **Gestión de la flota**: control de taxis y conductores, sabiendo quién está disponible en cada momento.
- **Gestión de viajes**: puedes consultar todo el proceso de cada servicio, ver el historial y hacer seguimiento.
- **Panel de control** donde puedas ver lo que pasa y sacar informes sencillos.
- **Registro de pagos**, llevando un control de lo cobrado y lo pendiente.
- **Gestión de direcciones** para organizar mejor los servicios.

---

## Instalación

Para ejecutar LanzaTaxi localmente, sigue estos pasos:

### Requisitos previos:

- PHP >= 8.2, y todas las extensiones necesarias:
```
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php php-cli php-mbstring php-xml php-bcmath php-curl php-zip unzip curl -y
```
Confirma la instalación de PHP:
```
php -v
```
- Composer
```
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
Verifica la instalación:
```
composer --version
```
- MySQL
```
sudo apt install mysql-server php-mysql -y
```
Configura la base de datos y el usuario correspondiente:
```
sudo mysql
CREATE DATABASE lanzataxi_db;
CREATE USER 'lanzataxi_user'@'localhost' IDENTIFIED BY 'lanza_taxi';
GRANT ALL PRIVILEGES ON lanzataxi_db.* TO 'lanzataxi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
- Node.js
```
sudo apt install nodejs npm
```
Confirma la instalación:
```
node -v
npm -v
```
- Git
```
sudo apt install git
```
Confirma la instalación:
```
git --version
```

1. Clona el repositorio:
```
git clone https://github.com/Claudiagarcia05/lanzataxi.lan.git
```
2. Accede a la carpeta:
```
cd lanzataxi.lan
```
3. Otorga los permisos correspondientes:
```
sudo chmod 777 -R ./*
```
4. Instala las dependencias de Composer y de Node.js:
```
composer install
npm install
npm run build
```
5. Copia el archivo .env.example a un archivo .env.
6. Modifica estas líneas del .env:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lanzataxi_db
DB_USERNAME=lanzataxi_user
DB_PASSWORD=lanza_taxi
```
7. Genera la clave de encriptación:
```
php artisan key:generate
```
8. Ejecuta las migraciones y estos seeders, para rellenar los datos en la base de datos:
```
php artisan migrate
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=TarifaSeeder
php artisan db:seed --class=HorarioSeeder
```
Si quieres, también puedes ejecutar este seeder:
```
php artisan db:seed --class=EventoSeeder
```
9. Para poder almacenar las imágenes para los eventos, ejecuta el siguiente comando:
```
php artisan storage:link
```
10. Inicia el servicio:
```
php artisan serve
```

De esta manera, si accedes por 127.0.0.1:8000, la página debe aparecer sin problema.

---

## Visita el Proyecto Online

Puedes visitar la página web [aquí](https://lanzataxi.es/)

---

## Vídeo de Youtube

Puedes ver el vídeo del proyecto en Youtube [aquí](    )
