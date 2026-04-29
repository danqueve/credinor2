# Plan de Proyecto v2.2 — Sistema de Gestión de Préstamos "Credinor"

**Versión:** 2.2 **Fecha de revisión:** 29/04/2026 **Ubicación:** San Miguel de Tucumán, Argentina **Negocio:** Préstamos de dinero en cuotas con sólo DNI

---

## 0\. Changelog respecto a v2.1

| \# | Cambio | Motivo |
| :---- | :---- | :---- |
| 1 | Módulo **Rendiciones CONFIRMADO** (ya no es opcional) | Habrá cobradores en calle que reciben efectivo y transferencia |
| 2 | **Carga bulk de pagos** en pantalla de rendición (grilla, no modal) | Frecuencia diaria genera alto volumen de pagos |
| 3 | Frecuencia agregada: **diaria** (ENUM ahora: diaria, semanal, quincenal, mensual) | A pedido del dueño |
| 4 | Configuración `dias_cobranza` (saltar domingos/feriados o no) | Decisión operativa pendiente |
| 5 | **Nuevo flujo de cálculo de cuota**: input directo de capital \+ cuotas \+ valor\_cuota | El admin piensa en montos, no en porcentajes |
| 6 | `interes_pct` pasa a ser **calculado** (interés implícito), no input | Refleja la operación real |
| 7 | Eliminada validación de min/max de capital prestado | A pedido del dueño |
| 8 | Nueva regla **RN-12**: un cliente puede tener N créditos activos simultáneos | Aclaración del dueño |
| 9 | Vista de **ficha cliente unificada** mostrando todos sus créditos | Consecuencia de RN-12 |

---

## 1\. Resumen Ejecutivo

**Credinor** es una plataforma web para gestionar la operación financiera completa de un negocio de **préstamos de dinero en cuotas**: clientes, créditos, pagos, rendiciones y reportes. La filosofía de **centralización administrativa** se mantiene: el Administrador es el único que carga datos transaccionales, y los cobradores operan en modo consulta.

**Diferenciadores clave del sistema:**

- **Fecha de pago real ≠ Fecha de registro:** evita injusticias cuando los pagos se cargan con demora administrativa.  
- **PWA con consulta offline:** los cobradores en calle consultan saldos aunque no tengan señal.  
- **Auditoría completa:** todo movimiento financiero queda trazado con usuario y timestamp.  
- **Sin recargos por mora por default:** el sistema queda preparado pero la mora arranca desactivada.  
- **Multi-crédito por cliente:** un mismo cliente puede tener varios préstamos activos simultáneos.  
- **Cálculo directo:** el admin ingresa monto prestado \+ cuotas \+ valor cuota; el sistema calcula el resto.  
- **Carga bulk de pagos:** rendiciones diarias optimizadas para volumen alto.

---

## 2\. Objetivos y KPIs medibles

| Objetivo | KPI | Meta a 3 meses |
| :---- | :---- | :---- |
| Eliminar errores de carga manual | Pagos corregidos / total pagos | \< 1% |
| Reducir tiempo de consulta de saldo | Segundos por consulta | \< 5 seg |
| Velocidad de carga de rendición | Pagos cargados por minuto en grilla | ≥ 20 |
| Visibilizar atraso real | % cartera con cuotas vencidas | Visible en dashboard |
| Disponibilidad del sistema | Uptime mensual | ≥ 99.5% |

---

## 3\. Stack Tecnológico

| Capa | Tecnología | Justificación |
| :---- | :---- | :---- |
| Backend | **PHP 8.1+** (POO \+ PDO \+ tipado estricto) | Hosting cPanel barato y compatible |
| Base de datos | **MySQL 8.0+** | Relacional, consistente, transacciones ACID |
| Frontend CSS | **Bootstrap 5.3** | Mobile-first, componentes listos |
| JavaScript | **Vanilla JS \+ Alpine.js** | Suficiente para AJAX y reactividad de grilla |
| AJAX | **Fetch API** \+ endpoints `/api/*.php` | Estándar moderno |
| PWA | **Service Worker \+ Workbox** | Caché offline para cobradores |
| Servidor desarrollo | **WAMP** |  |
| Servidor producción | **VPS o cPanel** | A definir |
| Control de versiones | **Git \+ GitHub** | Estándar |

### Librerías composer

{

  "require": {

    "php": "\>=8.1",

    "vlucas/phpdotenv": "^5.6",

    "phpmailer/phpmailer": "^6.9",

    "mpdf/mpdf": "^8.2",

    "phpoffice/phpspreadsheet": "^2.0"

  },

  "require-dev": {

    "phpunit/phpunit": "^10.0"

  }

}

---

## 4\. Arquitectura del Sistema

### 4.1. Patrón: MVC \+ Service Layer

credinor/

├── public/

│   ├── index.php              \# router / front controller

│   ├── api/                   \# endpoints AJAX (JSON)

│   │   ├── pagos.php

│   │   ├── pagos\_bulk.php     \# carga masiva en rendición

│   │   ├── creditos.php

│   │   ├── rendiciones.php

│   │   └── consultas.php

│   ├── assets/

│   └── service-worker.js

├── app/

│   ├── Controllers/

│   ├── Models/

│   ├── Services/              \# CreditoService, PagoService, RendicionService, CuotaCalendarioService, MoraService (placeholder)

│   ├── Repositories/

│   ├── Helpers/

│   ├── Middlewares/

│   └── Views/

├── config/

│   ├── database.php

│   ├── app.php                \# MORA\_HABILITADA=false, DIAS\_COBRANZA, etc.

│   └── routes.php

├── database/

│   ├── migrations/

│   ├── seeds/

│   └── backups/

├── storage/

│   ├── logs/

│   ├── uploads/

│   └── cache/

├── tests/

├── vendor/

├── .env.example

├── .gitignore

└── composer.json

### 4.2. Principios

- **Servicios contienen reglas de negocio** (cálculo de cuotas, generación de calendario, aplicación de pagos, anulación). Nada de lógica financiera en controladores.  
- **Repositorios encapsulan acceso a base** (queries PDO). Los modelos son objetos de datos.  
- **Endpoints AJAX devuelven JSON normalizado**: `{ ok: true, data: ..., errors: [] }`.  
- **Toda escritura financiera es transaccional**.  
- **Endpoints bulk** procesan validación en lote y devuelven resultado por fila.

---

## 5\. Roles y Permisos

| Funcionalidad | Administrador | Consulta |
| :---- | :---: | :---: |
| Login con 2FA opcional | ✅ | ✅ |
| Listar clientes | ✅ | ✅ |
| Ver ficha de cliente con todos sus créditos | ✅ | ✅ |
| Crear / Editar cliente | ✅ | ❌ |
| Listar créditos activos | ✅ | ✅ |
| Crear crédito | ✅ | ❌ |
| Anular crédito | ✅ | ❌ |
| Refinanciar crédito | ✅ | ❌ |
| Ver saldo y cuotas pendientes | ✅ | ✅ |
| Ver historial de pagos | ✅ | ✅ |
| Registrar pago individual | ✅ | ❌ |
| Cargar rendición (bulk pagos) | ✅ | ❌ |
| Anular pago | ✅ | ❌ |
| Generar reportes | ✅ | ❌ |
| Gestionar usuarios y personal | ✅ | ❌ |
| Ver logs de auditoría | ✅ | ❌ |

---

## 6\. Modelo de Datos

### 6.1. Tablas principales

#### `usuarios`

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_usuario | INT PK AUTO |  |
| username | VARCHAR(50) UNIQUE |  |
| password\_hash | VARCHAR(255) | argon2id |
| rol | ENUM('admin','consulta') |  |
| id\_personal | INT FK | NULL si es solo admin |
| activo | TINYINT(1) |  |
| ultimo\_login | DATETIME |  |
| intentos\_fallidos | TINYINT | rate limit |
| bloqueado\_hasta | DATETIME |  |
| created\_at, updated\_at, deleted\_at | TIMESTAMP |  |

#### `personal`

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_personal | INT PK AUTO |  |
| nombre | VARCHAR(120) |  |
| dni | VARCHAR(15) UNIQUE |  |
| telefono | VARCHAR(30) |  |
| rol\_operativo | SET('vendedor','cobrador','ambos') |  |
| id\_zona | INT FK NULL | zona asignada |
| comision\_pct | DECIMAL(5,2) DEFAULT 0 |  |
| estado | ENUM('activo','inactivo') |  |
| created\_at, updated\_at, deleted\_at |  |  |

#### `clientes`

**Importante:** un cliente puede tener N créditos activos simultáneos. La ficha del cliente debe mostrarlos todos con su estado.

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_cliente | INT PK AUTO |  |
| nombre | VARCHAR(80) |  |
| apellido | VARCHAR(80) |  |
| dni | VARCHAR(15) UNIQUE |  |
| direccion | VARCHAR(255) |  |
| id\_zona | INT FK NULL |  |
| referencia\_domicilio | VARCHAR(255) | "frente al kiosco azul" |
| latitud, longitud | DECIMAL(10,7) NULL | opcional, para mapa |
| telefono\_principal | VARCHAR(30) |  |
| telefono\_alternativo | VARCHAR(30) NULL |  |
| score\_interno | TINYINT DEFAULT 3 | 1-5, calculado por historial |
| observaciones | TEXT |  |
| created\_at, updated\_at, deleted\_at |  |  |

#### `zonas`

| Campo | Tipo |
| :---- | :---- |
| id\_zona | INT PK |
| nombre | VARCHAR(80) — ej. "Bº San Cayetano" |
| id\_cobrador\_default | INT FK personal NULL |

#### `creditos`

**Cálculo automático del préstamo:** el admin ingresa **capital prestado**, **cantidad de cuotas** y **valor de cada cuota**. El sistema calcula automáticamente `monto_total = cantidad_cuotas × valor_cuota` y deriva el `interes_implicito` para reportes.

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_credito | INT PK AUTO |  |
| codigo | VARCHAR(20) UNIQUE | ej. CR-2026-00045 |
| id\_cliente | INT FK | un cliente puede tener varios |
| id\_vendedor | INT FK personal NULL |  |
| id\_cobrador | INT FK personal NULL |  |
| capital | DECIMAL(12,2) | monto prestado en mano (input) |
| cantidad\_cuotas | SMALLINT | input |
| valor\_cuota | DECIMAL(12,2) | input |
| monto\_total | DECIMAL(12,2) | **calculado:** cantidad\_cuotas × valor\_cuota |
| interes\_implicito | DECIMAL(12,2) | **calculado:** monto\_total \- capital \- gastos\_admin (informativo) |
| interes\_implicito\_pct | DECIMAL(6,2) | **calculado:** porcentaje sobre capital (informativo) |
| gastos\_admin | DECIMAL(12,2) DEFAULT 0 | opcional |
| frecuencia | ENUM('diaria','semanal','quincenal','mensual') |  |
| fecha\_inicio | DATE | fecha primera cuota |
| fecha\_fin\_estimada | DATE | calculada según frecuencia y `dias_cobranza` |
| saldo\_pendiente | DECIMAL(12,2) | denormalizado para performance |
| destino\_opcional | VARCHAR(120) NULL | texto libre opcional |
| estado | ENUM('activo','finalizado','anulado','refinanciado','incobrable') |  |
| id\_credito\_origen | INT FK NULL | si proviene de refinanciación |
| observaciones | TEXT |  |
| created\_by, updated\_by | INT FK usuarios |  |
| created\_at, updated\_at, deleted\_at |  |  |

#### `cuotas`

Una fila por cada cuota esperada. Generada automáticamente al crear el crédito según frecuencia y configuración de `dias_cobranza`.

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_cuota | INT PK AUTO |  |
| id\_credito | INT FK |  |
| numero\_cuota | SMALLINT | 1, 2, 3... |
| fecha\_vencimiento | DATE |  |
| monto\_esperado | DECIMAL(12,2) | \= valor\_cuota del crédito |
| monto\_pagado | DECIMAL(12,2) DEFAULT 0 | acumulado |
| monto\_recargo | DECIMAL(12,2) DEFAULT 0 | **siempre 0 por default**, preparado para futuro |
| estado | ENUM('pendiente','parcial','pagada','vencida','condonada') |  |
| fecha\_pagada | DATE NULL | última fecha de pago aplicada |

#### `pagos`

| Campo | Tipo | Notas |
| :---- | :---- | :---- |
| id\_pago | INT PK AUTO |  |
| id\_credito | INT FK |  |
| id\_cobrador | INT FK personal NULL | quién recibió la plata |
| monto\_pagado | DECIMAL(12,2) |  |
| forma\_pago | ENUM('efectivo','transferencia','mp','otro') |  |
| referencia\_externa | VARCHAR(60) NULL | nro de transferencia, etc. |
| **fecha\_pago\_real** | DATE | cuándo el cliente entregó la plata |
| **fecha\_registro** | TIMESTAMP DEFAULT CURRENT | cuándo se cargó al sistema |
| id\_rendicion | INT FK NULL | rendición a la que pertenece |
| observaciones | TEXT |  |
| anulado | TINYINT(1) DEFAULT 0 |  |
| motivo\_anulacion | TEXT NULL | obligatorio si `anulado=1` |
| anulado\_por | INT FK usuarios |  |
| anulado\_at | DATETIME |  |
| created\_by | INT FK usuarios |  |
| created\_at, updated\_at, deleted\_at |  |  |

#### `pago_cuotas` (tabla puente)

Un pago puede aplicarse a varias cuotas (cliente paga 2 cuotas atrasadas juntas).

| Campo | Tipo |
| :---- | :---- |
| id\_pago | INT FK |
| id\_cuota | INT FK |
| monto\_aplicado | DECIMAL(12,2) |

#### `rendiciones` *(MÓDULO CONFIRMADO)*

Registro digital del momento en que un cobrador entrega al Admin la plata cobrada en calle (efectivo \+ transferencias).

| Campo | Tipo |
| :---- | :---- |
| id\_rendicion | INT PK |
| id\_cobrador | INT FK personal |
| fecha\_rendicion | DATE |
| total\_efectivo\_declarado | DECIMAL(12,2) — efectivo que trae el cobrador |
| total\_transferencias\_declarado | DECIMAL(12,2) — transferencias declaradas |
| total\_declarado | DECIMAL(12,2) — suma de ambos (calculado) |
| total\_registrado | DECIMAL(12,2) — suma de pagos cargados |
| diferencia | DECIMAL(12,2) — total\_declarado \- total\_registrado |
| estado | ENUM('borrador','conciliada','con\_diferencia') |
| observaciones | TEXT |
| created\_by, created\_at |  |

#### `recibos`

PDF generado por cada pago, con número de comprobante.

| Campo | Tipo |
| :---- | :---- |
| id\_recibo | INT PK |
| id\_pago | INT FK UNIQUE |
| numero | VARCHAR(20) UNIQUE — ej. R-2026-00123 |
| pdf\_path | VARCHAR(255) |
| created\_at |  |

#### `auditoria`

Log inmutable de todo movimiento financiero o de seguridad.

| Campo | Tipo |
| :---- | :---- |
| id\_log | BIGINT PK AUTO |
| id\_usuario | INT FK |
| accion | VARCHAR(60) — `pago.create`, `credito.anular`, `login.fail`... |
| entidad | VARCHAR(40) |
| entidad\_id | INT |
| datos\_antes | JSON |
| datos\_despues | JSON |
| ip | VARCHAR(45) |
| user\_agent | VARCHAR(255) |
| created\_at | TIMESTAMP DEFAULT CURRENT |

#### `comisiones` (opcional)

Calculada automáticamente, liquidable mensualmente.

| Campo | Tipo |
| :---- | :---- |
| id\_comision | INT PK |
| id\_personal | INT FK |
| periodo | VARCHAR(7) — '2026-04' |
| tipo | ENUM('venta','cobranza') |
| monto\_base | DECIMAL(12,2) |
| pct | DECIMAL(5,2) |
| monto\_comision | DECIMAL(12,2) |
| pagada | TINYINT(1) |

### 6.2. Diagrama relacional simplificado

zonas ──┐

        ▼

     clientes ────── (1 cliente : N créditos)

        │

        ▼

    creditos ◄── personal (vendedor/cobrador)

       │

       ├──► cuotas (calendario de vencimientos)

       │

       └──► pagos ──► pago\_cuotas ──► cuotas

              │

              ├──► recibos

              │

              └──► rendiciones (cobrador, día, conciliación)

usuarios ──► auditoria (registra todo)

### 6.3. Soft delete

Toda tabla operativa (`creditos`, `pagos`, `clientes`, `usuarios`) implementa `deleted_at TIMESTAMP NULL`. Las queries filtran por defecto `WHERE deleted_at IS NULL`. Eliminar nunca es físico.

---

## 7\. Flujos Operativos Críticos

### 7.1. Alta de Crédito (flujo nuevo, cálculo directo)

1. Vendedor cierra operación con el cliente y envía datos al Admin.  
     
2. Admin entra a "Nuevo Crédito":  
     
   - Busca cliente por DNI o crea uno nuevo.  
   - **Si el cliente ya tiene créditos activos**, el sistema lo muestra en la pantalla con un cartel: *"Este cliente tiene N crédito(s) activo(s) \- saldo total pendiente: $XX"*. No bloquea la creación; es solo informativo.  
   - Ingresa: **capital prestado**, **cantidad de cuotas**, **valor de cada cuota**, frecuencia (diaria/semanal/quincenal/mensual), fecha primera cuota.  
   - Opcional: gastos administrativos, destino, observaciones.  
   - Selecciona Vendedor y Cobrador desde `<select>` poblados de `personal`.

   

3. **Sistema calcula y muestra en vivo (antes de guardar)**:  
     
   Monto prestado:        $ 100.000  
     
   × Cantidad de cuotas:        20  
     
   × Valor cuota:         $   7.000  
     
   ─────────────────────────────────  
     
   Total a devolver:      $ 140.000  
     
   Interés implícito:     $  40.000  (40,00%)  
     
4. **Previsualización de calendario de cuotas** antes de confirmar (ver 7.2).  
     
5. Al confirmar, en una transacción:  
     
   - Crea registro en `creditos`.  
   - Genera N filas en `cuotas` con vencimientos según frecuencia y configuración `dias_cobranza`.  
   - Asigna código (`CR-2026-00045`).  
   - Setea `saldo_pendiente = monto_total`.

   

6. Auditoría: `accion='credito.create'`.  
     
7. Opcional: imprime/envía PDF del contrato/pagaré.

### 7.2. Generación del calendario de cuotas

El servicio `CuotaCalendarioService::generar()` recorre desde `fecha_inicio` y crea N cuotas según la frecuencia:

| Frecuencia | Lógica de incremento |
| :---- | :---- |
| `diaria` | \+1 día calendario, **saltando los días excluidos en `config.dias_cobranza`** |
| `semanal` | \+7 días |
| `quincenal` | \+15 días |
| `mensual` | \+1 mes calendario |

Configuración en `config/app.php`:

return \[

    'dias\_cobranza' \=\> \[

        'lunes' \=\> true,

        'martes' \=\> true,

        'miercoles' \=\> true,

        'jueves' \=\> true,

        'viernes' \=\> true,

        'sabado' \=\> true,

        'domingo' \=\> false,    // ← decisión pendiente del dueño

    \],

    'feriados\_excluidos' \=\> false,  // a futuro: tabla de feriados

    // ...

\];

**Decisión pendiente:** ¿la frecuencia diaria incluye domingos? ¿Y feriados? Lo configuramos en Fase 0\.

### 7.3. Registro de Pago Individual

1. Admin entra a "Registrar Pago", busca el crédito por código, DNI o nombre del cliente.  
2. Si el cliente tiene varios créditos, el sistema muestra todos para que elija a cuál aplicar.  
3. Sistema muestra cuotas pendientes y el saldo actual del crédito seleccionado.  
4. Admin ingresa: monto, forma de pago (efectivo/transferencia/MP), **fecha\_pago\_real**, cobrador, referencia externa, observaciones.  
5. **Previsualización antes de confirmar:** el sistema muestra cómo se va a aplicar el monto a las cuotas (FIFO).  
6. Al confirmar, en transacción:  
   - Inserta en `pagos`.  
   - Inserta filas en `pago_cuotas` aplicando FIFO.  
   - Actualiza `cuotas.monto_pagado` y `cuotas.estado`.  
   - Recalcula `creditos.saldo_pendiente`.  
   - Si saldo llega a 0 → `creditos.estado='finalizado'`.  
   - Genera el recibo PDF.  
   - Registra en auditoría.

### 7.4. Carga Bulk de Pagos en Rendición *(NUEVO – optimizado para volumen)*

Diseñado para cargar 50-200 pagos rápido al cerrar el día/semana del cobrador.

**Flujo:**

1. Admin entra a "Nueva Rendición":  
     
   - Selecciona cobrador y fecha de rendición.  
   - Ingresa total efectivo declarado y total transferencias declaradas.

   

2. Pantalla principal: **grilla editable** con buscador por DNI/código rápido.  
     
   ┌─────────────────────────────────────────────────────────┐  
     
   │ \[Buscar DNI/código...\]  \[Escanear QR\]  \[+ Agregar fila\] │  
     
   ├──────┬──────────┬────────┬────────┬──────────┬─────────┤  
     
   │ DNI  │ Cliente  │ Crédito│ Monto  │ Forma    │ Acción  │  
     
   ├──────┼──────────┼────────┼────────┼──────────┼─────────┤  
     
   │27123…│ J. Pérez │ CR-045 │ 7000   │ Efectivo │  ❌     │  
     
   │30456…│ M. López │ CR-088 │ 5000   │ Transf.  │  ❌     │  
     
   │ ...                                                      │  
     
   └─────────────────────────────────────────────────────────┘  
     
   Total registrado: $ XXX.XXX  
     
   Total declarado:  $ XXX.XXX  
     
   Diferencia:       $   X.XXX  ⚠ con diferencia  
     
3. Al tipear DNI o código, autocompleta cliente y muestra créditos activos para elegir.  
     
4. La fecha\_pago\_real toma por default la fecha de la rendición (editable por fila).  
     
5. Al cerrar, se valida todo en lote y se inserta en una sola transacción.  
     
6. Estado final: `conciliada` (diferencia 0\) o `con_diferencia` (queda pendiente de revisión).

### 7.5. Mora y Recargos *(DESACTIVADO POR DEFAULT)*

El sistema arranca **sin recargos por mora**. La estructura queda lista para activarla a futuro sin tocar el modelo de datos.

**Comportamiento por default:**

- `cuotas.monto_recargo` siempre en 0\.  
- El job diario marca cuotas como `vencida` cuando `fecha_vencimiento < HOY` y `estado='pendiente'`. Solo informativo (rojos en pantalla, reportes), sin agregar cargos al saldo.

**Configuración para activar a futuro** (`config/app.php`):

'mora' \=\> \[

    'habilitada' \=\> false,        // ← cambiar a true para activar

    'tolerancia\_dias' \=\> 3,

    'tramo\_1\_dias' \=\> \[4, 15\],

    'tramo\_1\_pct' \=\> 5,

    'tramo\_2\_dias' \=\> \[16, 999\],

    'tramo\_2\_pct' \=\> 10,

    'tramo\_2\_diario\_pct' \=\> 0.5,

\],

Cuando se active, `MoraService` calculará y cargará los recargos en `cuotas.monto_recargo` durante el job diario. **Nunca se modificará el `monto_esperado` original** (trazabilidad histórica).

### 7.6. Anulación de Pago

- Solo Admin. Requiere motivo obligatorio (mín. 10 caracteres).  
- Marca `anulado=1`, registra usuario y timestamp.  
- Reversa la aplicación a cuotas (`pago_cuotas`) y recalcula saldo del crédito.  
- Auditoría con datos antes/después.

### 7.7. Refinanciación

Cliente con cuotas atrasadas pide refinanciar el saldo en nuevas cuotas.

1. Admin selecciona crédito original → "Refinanciar".  
2. Sistema toma `saldo_pendiente` como nuevo capital base.  
3. Admin ingresa nueva cantidad de cuotas y nuevo valor de cuota.  
4. Genera un nuevo crédito con `id_credito_origen = id_credito_anterior`.  
5. Crédito anterior pasa a `estado='refinanciado'`.

### 7.8. Crédito Incobrable

- Admin marca como `incobrable` con motivo.  
- Saldo se contabiliza como pérdida en reportes.  
- No se elimina; queda historial.

---

## 8\. Reglas de Negocio Explícitas

| \# | Regla |
| :---- | :---- |
| RN-01 | Todo movimiento financiero corre dentro de una transacción PDO. |
| RN-02 | `fecha_pago_real ≤ fecha_registro` siempre. |
| RN-03 | `fecha_pago_real ≥ fecha_inicio del crédito` siempre. |
| RN-04 | Un pago no puede ser mayor al saldo del crédito. |
| RN-05 | Un crédito finalizado no admite nuevos pagos (salvo reapertura por anulación). |
| RN-06 | Las cuotas se aplican siempre por orden FIFO (más antigua primero). |
| RN-07 | El monto del pago se distribuye automáticamente entre cuotas; el Admin puede ver la previsualización antes de confirmar. |
| RN-08 | La anulación requiere motivo y queda auditada; nunca se borra físicamente. |
| RN-09 | Un cliente con un crédito en estado `incobrable` no puede tener un nuevo crédito sin override del Admin (con confirmación). |
| RN-10 | El score interno del cliente se recalcula automáticamente al finalizar/incobrar créditos. |
| RN-11 | **Mora desactivada:** el campo `monto_recargo` se mantiene en 0 mientras `config.mora.habilitada=false`. |
| RN-12 | **Multi-crédito:** un cliente puede tener N créditos activos simultáneos. La ficha de cliente debe mostrarlos todos. Al registrar un pago, el sistema muestra todos los créditos activos del cliente para que el Admin elija. |
| RN-13 | **Cálculo del crédito:** `monto_total = cantidad_cuotas × valor_cuota`. El interés implícito se calcula automáticamente y NO es un input. |
| RN-14 | **Calendario de cuotas:** se genera al crear el crédito según la frecuencia y la configuración `dias_cobranza` (qué días se cobra). |
| RN-15 | **Rendiciones:** el total declarado por el cobrador se desglosa en efectivo \+ transferencias. La conciliación verifica que la suma cargada coincida con la suma declarada. |

---

## 9\. Seguridad

### 9.1. Autenticación

- Hash de contraseñas con `password_hash(PASSWORD_ARGON2ID)`.  
- Política mínima de contraseñas: 10+ caracteres, mayúscula, número, símbolo.  
- **Rate limit en login**: 5 intentos fallidos → bloqueo de 15 min.  
- 2FA opcional por TOTP para Admin.  
- Sesión PHP con `session_regenerate_id` post-login y timeout 30 min.  
- Cookies con `HttpOnly`, `Secure`, `SameSite=Strict`.

### 9.2. Autorización

- Middleware `RoleMiddleware` valida rol en cada controlador.  
- Ningún endpoint AJAX confía en el rol enviado por cliente; siempre verifica desde sesión.

### 9.3. CSRF

- Token CSRF en todos los formularios POST y peticiones AJAX (header `X-CSRF-Token`).  
- Validación obligatoria en `CsrfMiddleware`.

### 9.4. Inputs

- **PDO con prepared statements** siempre. Nunca concatenación SQL.  
- Sanitizer central para escape HTML en outputs.  
- Validación tipada: montos `DECIMAL`, fechas `DateTime::createFromFormat`, enteros `filter_var`.

### 9.5. Logs y monitoreo

- Errores PHP a archivo (no a pantalla en producción).  
- `auditoria` registra acciones financieras y eventos de seguridad.

### 9.6. Backups

- Cron diario con `mysqldump` comprimido y rotación 30 días.  
- Backup semanal off-site (Google Drive con rclone, o S3 compatible).  
- Probar restore mensualmente.

---

## 10\. Reportes (Dashboard Admin)

| Reporte | Filtros | Para qué |
| :---- | :---- | :---- |
| Cartera total activa | Cobrador, zona, vendedor | Cuánto hay prestado vigente |
| Cuotas vencidas (aging) | Tramos: 1-15, 16-30, 31-60, 60+ días | Identificar cartera en riesgo |
| Cobranza diaria | Fecha, cobrador | Real vs esperado |
| Performance cobrador | % cobrado vs esperado | Evaluar equipo |
| Performance vendedor | Volumen, % atraso generado | Evaluar equipo |
| Flujo de caja proyectado | Próximos 30/60/90 días | Planificar liquidez para nuevos préstamos |
| Cuotas que vencen hoy | Cobrador, zona | Hoja de ruta del día |
| Clientes con cuotas atrasadas | Con teléfono y dirección | Acción de cobranza |
| Capital prestado vs recuperado | Período | Rentabilidad real |
| Rendiciones con diferencia | Período, cobrador | Detectar diferencias persistentes |
| **Clientes con múltiples créditos activos** | Saldo total | Ver exposición por cliente |

Cada reporte exporta a PDF (mPDF) y Excel (PhpSpreadsheet).

---

## 11\. UX para Personal de Campo (vista Consulta)

### 11.1. PWA y modo offline

- Service Worker cachea las vistas de consulta y los datos del último login.  
- Al volver con señal, sincroniza datos.  
- Indicador visual claro de "Sin conexión \- mostrando datos de hace X horas".

### 11.2. Pantallas críticas (mobile-first)

1. **Dashboard cobrador del día**: lista de cuotas que vencen HOY, ordenada por zona/dirección. Específicamente útil con frecuencia diaria.  
2. **Búsqueda rápida**: input grande, busca por nombre, DNI, dirección o código de crédito.  
3. **Ficha cliente unificada**: muestra **todos los créditos** del cliente (activos, finalizados, refinanciados), con saldo de cada uno y próxima cuota. Crítico por RN-12.  
4. **Historial de pagos**: lista cronológica con fechas reales.  
5. **Mapa opcional** (si hay lat/lng): hoja de ruta del día.

### 11.3. Integraciones útiles

- **Botón WhatsApp**: abre chat con mensaje template "Hola {cliente}, te recordamos que tu cuota de ${monto} vence el {fecha}".  
- **Compartir saldo**: genera imagen con el estado de cuenta, lista para enviar.  
- **Llamar**: link `tel:` directo.

---

## 12\. Roadmap (8 fases, \~10-11 semanas)

### Fase 0 — Descubrimiento y Mockups (Semana 1\)

- Validar reglas de negocio con el dueño (revisar 5-10 préstamos reales y simularlos en el modelo).  
- **Decidir `dias_cobranza`** para frecuencia diaria (¿se cobra domingos? ¿feriados?).  
- Definir parámetros base (Apéndice A).  
- Confirmar lista de zonas reales.  
- Mockups Figma o boceto de las 9 pantallas principales (incluida grilla de rendición).

### Fase 1 — Fundamentos (Semana 2\)

- Crear repo, estructura de carpetas, composer.  
- Migrations de TODAS las tablas.  
- Conexión PDO \+ .env \+ Helpers básicos.  
- Login \+ sesión \+ middleware de rol \+ CSRF \+ rate limit.  
- Layout base responsive (Bootstrap 5).  
- Auditoría central funcionando.

### Fase 2 — Entidades Básicas (Semana 3\)

- CRUD: Personal, Zonas.  
- CRUD: Clientes (versión simplificada).  
- Búsqueda por DNI/nombre con autocomplete AJAX.  
- **Ficha cliente unificada** (placeholder, se completa en fase 3).  
- Validar permisos por rol.

### Fase 3 — Módulo Core: Créditos (Semana 4-5)

- Servicio `CreditoService::crear()` con cálculo automático del monto\_total e interés implícito.  
- Servicio `CuotaCalendarioService::generar()` con soporte para 4 frecuencias y `dias_cobranza`.  
- Vista "Nuevo Crédito" con previsualización de cálculo en vivo y calendario de cuotas.  
- **Aviso si el cliente tiene créditos activos** (no bloquea, informa).  
- Listado de créditos con filtros.  
- **Ficha cliente unificada completa** (todos los créditos del cliente).  
- Refinanciación.

### Fase 4 — Pagos Individuales (Semana 6\)

- Servicio `PagoService::registrar()` con aplicación FIFO a cuotas.  
- Modal de registrar pago con `fecha_pago_real`, selección de crédito si hay varios.  
- Anulación de pago con motivo.  
- Job diario que marca cuotas como `vencida` (sin recargo).  
- Generación de recibos PDF.

### Fase 4b — Rendiciones con Carga Bulk (Semana 7\)

- Servicio `RendicionService` con conciliación efectivo \+ transferencias.  
- Pantalla grilla de carga bulk con autocomplete por DNI/código.  
- Validación en lote y commit en transacción única.  
- Reporte de rendiciones con diferencias.

### Fase 5 — Vista Consulta y PWA (Semana 8\)

- Dashboard cobrador del día (mobile-first).  
- Búsqueda rápida.  
- Ficha de cliente con historial.  
- Service Worker \+ manifest PWA.  
- Integración WhatsApp y `tel:`.

### Fase 6 — Reportes y Analíticas (Semana 9\)

- Reportes con export PDF/Excel.  
- Dashboard Admin con KPIs visuales (Chart.js).  
- Cálculo automático de comisiones (si aplica).

### Fase 7 — QA, UAT y Despliegue (Semana 10-11)

- Pruebas con el dueño cargando 1 semana real en paralelo.  
- Despliegue a producción (subdominio sugerido: `app.credinor.com.ar`).  
- HTTPS, backups automáticos, cron diario.  
- Manual breve para Admin y para personal de campo.  
- 2 semanas de soporte post-despliegue.

---

## 13\. Convenciones de Código

| Elemento | Convención |
| :---- | :---- |
| Archivos PHP | `PascalCase` clases, `snake_case` scripts/endpoints |
| Clases | `PascalCase` |
| Métodos y variables | `camelCase` |
| Tablas y columnas SQL | `snake_case` |
| Constantes | `UPPER_SNAKE_CASE` |
| Endpoints AJAX | `/api/recurso_accion.php` |
| Respuesta JSON estándar | `{ ok: bool, data: any, errors: string[], message?: string }` |
| Commits Git | Convencional: `feat:`, `fix:`, `refactor:`, `docs:` |
| Rama principal | `main` |
| Ramas de trabajo | `feature/<nombre>`, `fix/<nombre>` |
| Variables de entorno | `.env` con `vlucas/phpdotenv`, NUNCA commiteado |

---

## 14\. Prompt Maestro v2.2 (para iniciar chats con IA)

Actuá como un Desarrollador Full-Stack Senior experto en PHP 8.1+ (POO, PDO,

tipado estricto), MySQL 8 y Bootstrap 5, con experiencia en sistemas

financieros del mercado argentino (préstamos en cuotas).

Estamos desarrollando "Credinor", un sistema de gestión de préstamos de

dinero en cuotas para una empresa de San Miguel de Tucumán, Argentina.

CONTEXTO TÉCNICO:

\- Backend: PHP 8.1+ con PDO y POO, arquitectura MVC \+ Service Layer.

\- BD: MySQL 8 con InnoDB y FKs estrictas.

\- Frontend: Bootstrap 5.3 mobile-first \+ Vanilla JS \+ Alpine.js \+ Fetch API.

\- Estructura: app/Controllers, app/Services (lógica de negocio),

  app/Repositories (queries), app/Models (DTOs), public/api (endpoints AJAX).

\- Estándar de respuesta JSON: { ok: bool, data, errors: \[\] }.

\- Naming: PascalCase clases, camelCase métodos, snake\_case columnas SQL.

NEGOCIO:

\- Préstamos de dinero en efectivo, con sólo DNI.

\- Frecuencias soportadas: DIARIA, semanal, quincenal, mensual.

\- Cobradores en calle reciben efectivo y transferencias; rinden al Admin.

\- Un cliente puede tener múltiples créditos activos simultáneos.

REGLAS CRÍTICAS DE NEGOCIO:

1\. Solo el rol "admin" carga datos. El rol "consulta" (cobradores) es SOLO LECTURA.

2\. \*\*Cálculo del crédito:\*\* el admin ingresa CAPITAL prestado, CANTIDAD DE CUOTAS

   y VALOR DE CUOTA. El sistema calcula automáticamente:

   \- monto\_total \= cantidad\_cuotas × valor\_cuota

   \- interes\_implicito \= monto\_total \- capital \- gastos\_admin

   \- interes\_implicito\_pct \= interes\_implicito / capital \* 100

   El admin NO ingresa porcentajes de interés; piensa en montos.

3\. Existe una tabla \`cuotas\` (una fila por cuota esperada) que es la fuente

   de verdad para aplicar pagos por orden FIFO.

4\. El calendario de cuotas se genera automáticamente al crear el crédito,

   respetando la configuración \`dias\_cobranza\` (por ejemplo, saltar domingos

   en frecuencia diaria).

5\. Los pagos tienen DOS fechas: \`fecha\_pago\_real\` (cuándo el cliente entregó

   la plata, elegida por el Admin) y \`fecha\_registro\` (timestamp automático).

   NUNCA confundirlas.

6\. \*\*Multi-crédito por cliente:\*\* un cliente puede tener N créditos activos.

   La ficha de cliente debe mostrarlos TODOS. Al registrar un pago se debe

   elegir a cuál crédito aplicarlo.

7\. \*\*Rendiciones:\*\* los cobradores rinden cada día/semana al Admin. La

   rendición desglosa efectivo \+ transferencias y se concilia con la suma

   de pagos cargados. La pantalla de carga es una GRILLA tipo bulk para

   cargar muchos pagos rápido.

8\. \*\*Mora DESACTIVADA por default\*\* (config.mora.habilitada=false).

   El campo \`cuotas.monto\_recargo\` siempre queda en 0\. El job diario solo

   marca cuotas como \`vencida\` con fines informativos.

9\. Toda escritura financiera corre en transacción PDO.

10\. Toda acción financiera o de seguridad genera un registro en la tabla

    \`auditoria\` con datos antes/después en JSON.

11\. Soft delete con columna \`deleted\_at\` en todas las tablas operativas.

12\. Los pagos pueden ser anulados (no borrados) con motivo obligatorio.

13\. Validación de inputs siempre del lado servidor; PDO prepared statements

    siempre; CSRF token en todos los POST.

CUANDO ESCRIBAS CÓDIGO:

\- Mostrá la query SQL antes del código PHP cuando sea relevante.

\- Devolvé el código completo del archivo, no fragmentos sueltos.

\- Comentá las decisiones de negocio en español.

\- Si una decisión depende de información que no tenés, preguntá antes de inventar.

Confirmá que entendiste el contexto y esperá mi siguiente instrucción.

---

## 15\. Apéndices

### A. Variables de configuración a definir antes de codear

| Parámetro | Estado | Valor |
| :---- | :---- | :---- |
| Rango de capital prestado | ✅ Sin validación de min/max | (libre) |
| Frecuencias soportadas | ✅ Definido | diaria, semanal, quincenal, mensual |
| Módulo Rendiciones | ✅ Confirmado | Activo |
| Cobranza recibe | ✅ Definido | efectivo \+ transferencia |
| Cliente puede tener N créditos | ✅ Definido | Sí, simultáneos |
| Cálculo del crédito | ✅ Definido | Input directo: capital \+ cuotas \+ valor\_cuota |
| **¿Días de cobranza para frecuencia diaria?** | ⏳ Pendiente | ¿incluye domingos? ¿feriados? |
| Frecuencia más común estimada | ⏳ Pendiente | \_\_\_\_ |
| Cantidad típica de cuotas | ⏳ Pendiente | \_\_\_\_ |
| Comisión vendedor | ⏳ Pendiente | \_\_\_\_ % |
| Comisión cobrador | ⏳ Pendiente | \_\_\_\_ % |
| Zonas operativas iniciales | ⏳ Pendiente | \_\_\_\_ |

### B. Riesgos conocidos y mitigaciones

| Riesgo | Mitigación |
| :---- | :---- |
| Pérdida de datos en VPS | Backups diarios \+ off-site semanal \+ restore test mensual |
| Adopción del personal | Capacitación corta \+ manual ilustrado \+ soporte 2 semanas |
| Errores de carga del Admin | Validaciones estrictas \+ previsualización antes de confirmar pago |
| Caída de internet en local | PWA modo offline para vista consulta |
| Manipulación de saldos | Auditoría \+ soft delete \+ anulación con motivo |
| **Volumen alto en frecuencia diaria** | Carga bulk en grilla \+ autocomplete rápido |
| **Diferencia persistente en rendiciones** | Reporte específico \+ alerta al Admin |
| **Cliente con varios créditos: aplicar pago al equivocado** | Confirmación visual antes de aplicar; opción de anular si se equivoca |

### C. Posibles integraciones a futuro (post-MVP)

- **WhatsApp Business API** para recordatorios automáticos.  
- **MercadoPago**: cobro online con link de pago (registra automáticamente como `forma_pago='mp'`).  
- **AFIP**: emisión de comprobantes electrónicos si se decide formalizar.  
- **Activación del módulo de mora** según política comercial.  
- **Tabla de feriados nacionales** para excluir automáticamente del calendario de cuotas diarias.

---

**Fin del plan v2.2** — Listo para validar con el dueño y arrancar Fase 0\.  
