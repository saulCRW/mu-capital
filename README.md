# MU Capital - Fondo de Inversión

  Proyecto integrador fullstack para gestión de fondos de inversión.

  ## Estructura del proyecto

  mu-capital/
  ├── backend/     Laravel API REST
  ├── mobile/      React Native (Expo) - App móvil
  └── web/         React + Vite - Aplicación web

  ## Requisitos previos

  - [Git](https://git-scm.com/download/win)
  - [Node.js](https://nodejs.org/) v18 o superior
  - [PHP 8.2+](https://www.php.net/)
  - [Composer](https://getcomposer.org/)
  - [Laragon](https://laragon.org/)

  ## Instalación

  ### 1. Clonar el repositorio
  git clone https://github.com/saulCRW/mu-capital.git
  cd mu-capital

  ### 2. Backend
  cd backend
  composer install
  copy .env.example .env
  php artisan key:generate
  php artisan migrate

  Editar .env con tus datos de base de datos:
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=mucapital
  DB_USERNAME=root
  DB_PASSWORD=

  ### 3. Web
  cd web
  npm install
  npm run dev
  Disponible en: http://localhost:5173

  ### 4. Móvil
  cd mobile
  npm install
  npx expo start
  Escanear el QR con Expo Go en tu celular.

  Si da error de proxy:
  $env:http_proxy=""
  npx expo start --clear

  ## Flujo de trabajo en equipo
  # Antes de trabajar
  git pull

  # Al terminar
  git add .
  git commit -m "descripción de cambios"
  git push

  ## Tecnologías
  - Backend: Laravel 12 + Sanctum
  - Base de datos: MySQL
  - Web: React 18 + TypeScript + Vite + TailwindCSS
  - Móvil: React Native + Expo
