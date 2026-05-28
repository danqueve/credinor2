# Credinor

Sistema de gestión de préstamos en cuotas desarrollado en PHP. Permite administrar clientes, créditos, cobros, caja y generar reportes en PDF y Excel.

---

## Requisitos

- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3
- Composer
- Servidor web con soporte para `.htaccess` (Apache / WAMP / cPanel)

---

## Instalación local

### 1. Clonar el repositorio

```bash
git clone https://github.com/danqueve/credinor2.git
cd credinor2
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
```

Editar `.env` con los datos del entorno:

```env
APP_URL=http://localhost/credinor2/public

DB_HOST=localhost
DB_PORT=3306
DB_NAME=nombre_base_de_datos
DB_USER=usuario
DB_PASS=contraseña

APP_KEY=clave_aleatoria_32_caracteres
```

### 4. Crear la base de datos

Importar el archivo SQL inicial en MySQL:

```bash
mysql -u root -p nombre_base_de_datos < database/credinor2.sql
```

O ejecutar las migraciones en orden desde `database/migrations/`.

### 5. Crear usuario administrador

```bash
php database/seed_admin.php
```

### 6. Acceder

```
http://localhost/credinor2/public
```

---

## Estructura del proyecto

```
credinor2/
├── app/
│   ├── Controllers/       # Lógica HTTP (MVC)
│   │   └── Api/           # Endpoints JSON internos
│   ├── Models/            # Entidades del dominio
│   ├── Repositories/      # Acceso a base de datos (PDO)
│   ├── Services/          # Lógica de negocio
│   ├── Helpers/           # Auth, Session, CSRF, View, DB
│   ├── Middlewares/       # Auth, CSRF, Roles
│   └── Views/             # Plantillas PHP
├── config/
│   └── routes.php         # Definición de rutas GET/POST
├── database/
│   ├── migrations/        # Scripts SQL por versión
│   └── credinor2.sql      # Dump completo de la base
├── public/
│   ├── index.php          # Front controller
│   ├── assets/            # CSS, JS, imágenes
│   └── .htaccess          # Reescritura de URLs
├── storage/
│   ├── recibos/           # PDFs generados
│   └── logs/
├── tests/
├── .env.example
└── composer.json
```

---

## Módulos

| Módulo | URL | Descripción |
|--------|-----|-------------|
| Dashboard | `/` | Resumen general: saldo, créditos activos, cobros del día |
| Clientes | `/clientes` | ABM de clientes con ficha completa |
| Créditos | `/creditos` | Alta, edición, refinanciación y anulación de créditos |
| Pagos | `/pagos` | Registro de cobros con asignación FIFO a cuotas |
| Caja | `/caja` | Movimientos manuales de ingreso/egreso con filtro por fechas y exportación PDF |
| Rendiciones | `/rendiciones` | Cierre de cobranza por cobrador |
| Comisiones | `/comisiones` | Cálculo y liquidación de comisiones al personal |
| Reportes | `/reportes` | Analíticas financieras, historial de movimientos paginado y exportación PDF/Excel |
| Vencimientos | `/reportes/vencimientos` | Créditos próximos a vencer |
| Personal | `/personal` | Gestión de vendedores, cobradores y zonas |
| Usuarios | `/usuarios` | Administración de usuarios del sistema |
| Consulta (mobile) | `/consulta` | Vista simplificada para cobradores en el campo |
| Mi cuenta | `/mi-cuenta` | Estado de cuenta para clientes |

---

## Roles y permisos

| Rol | Acceso |
|-----|--------|
| `admin` | Acceso total |
| `supervisor` | Consulta y operaciones, sin gestión de usuarios |
| `cobrador` | Solo vista `/consulta` (mobile) |
| `read_only` | Solo lectura en módulos principales |
| `cliente` | Solo `/mi-cuenta` |

---

## Autenticación

- Login con usuario y contraseña (hash bcrypt)
- **2FA opcional** vía TOTP (compatible con Google Authenticator / Authy)
- Sesiones con tiempo de expiración configurable (`SESSION_LIFETIME` en `.env`)
- Protección CSRF en todos los formularios POST

---

## Exportaciones

| Tipo | Formato | Módulos |
|------|---------|---------|
| Lista de clientes | PDF | Reportes |
| Lista de créditos | PDF | Reportes |
| Cobros por período | PDF | Reportes |
| Créditos en atraso | PDF | Reportes |
| Movimientos de caja | PDF | Caja |
| Recibos de pago | PDF (A5) | Pagos |
| Cobranza por cobrador | Excel | Reportes |

---

## Dependencias principales

| Paquete | Versión | Uso |
|---------|---------|-----|
| `vlucas/phpdotenv` | ^5.6 | Variables de entorno |
| `phpmailer/phpmailer` | ^6.9 | Envío de emails |
| `mpdf/mpdf` | ^8.2 | Generación de PDFs |
| `phpoffice/phpspreadsheet` | ^2.0 | Exportación Excel |
| `phpunit/phpunit` | ^9.5 | Tests unitarios (dev) |

---

## Despliegue en hosting (FTP)

1. Subir todos los archivos excepto `.env` y `vendor/`
2. Crear `.env` en el servidor con los datos de producción
3. Subir la carpeta `vendor/` **o** ejecutar `composer install` por SSH
4. Importar la base de datos
5. Verificar que el `.htaccess` esté activo (módulo `mod_rewrite`)

> El `APP_URL` en producción debe apuntar a la carpeta `public/` del dominio.  
> Ejemplo: `APP_URL=https://tudominio.com`

---

## Tests

```bash
./vendor/bin/phpunit
```

Los tests se encuentran en `tests/Unit/` y cubren servicios de créditos, pagos y TOTP.

---

## Licencia

Uso privado. Todos los derechos reservados — Credinor.
