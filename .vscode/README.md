# SSTManager - Backend API

Sistema de gestión para Seguridad y Salud en el Trabajo desarrollado con PHP y arquitectura MVC personalizada.

## 🚀 Características
- **Multi-Tenancy**: Soporte para múltiples empresas con perfiles independientes.
- **Autenticación JWT**: Seguridad basada en tokens para el acceso a la API.
- **CRUD Genérico**: Motor dinámico para gestión de tablas constantes.
- **Documentación OpenAPI**: Integración total con Swagger UI.
- **Base de Datos Incremental**: Script de instalación automática que maneja llaves foráneas y migraciones.

## 🛠️ Tecnologías utilizadas
- **Lenguaje**: PHP 8.0+
- **Base de Datos**: MySQL / MariaDB
- **Librerías**: 
  - `firebase/php-jwt` para autenticación.
  - `zircote/swagger-php` para documentación.

## 📋 Requisitos previos
1. Tener instalado un servidor local como **XAMPP**.
2. **Composer** instalado globalmente.

## 🔧 Instalación y Configuración

1. **Clonar el repositorio:**
   ```bash
   git clone [https://github.com/mestepa@1991/sstmanager-backend.git](https://github.com/mestepa@1991/sstmanager-backend.git)
   cd sstmanager-backend