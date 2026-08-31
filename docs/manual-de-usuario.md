# Manual de usuario — Plataforma de firma de documentos

Este manual describe las funciones disponibles para cada rol de usuario y los límites de cada uno. La aplicación cuenta con **dos roles**:

| Rol | Etiqueta | Descripción |
|---|---|---|
| `administrator` | Administrador | Gestiona usuarios y sube documentos para que los firmantes los firmen. |
| `signer` | Firmante | Recibe documentos asignados y los firma en el orden establecido. |

> Todos los usuarios nuevos que crea un administrador se asignan por defecto al rol **Firmante** salvo que el administrador elija otro rol al crearlos.

---

## 1. Acceso a la aplicación

No existe registro público: **solo un administrador puede crear cuentas**. Para poder entrar necesitas una cuenta creada por un administrador.

### 1.1 Funciones comunes a ambos roles

Ambos roles pueden:

- **Iniciar sesión** (correo + contraseña) y usar las **llaves de acceso (passkeys)**.
- **Restablecer la contraseña** olvidada (por correo) y **verificar el correo electrónico**.
- **Configurar la autenticación de dos factores (2FA)**: activarla/desactivarla y guardar códigos de recuperación.
- **Gestionar su seguridad**: cambiar la contraseña y administrar sus passkeys.
- **Editar su perfil**: nombre y correo electrónico.
- **Eliminar su propia cuenta**.
- **Cambiar la apariencia** del sistema (tema claro/oscuro).
- Ver el **panel (Dashboard)**.

### 1.2 Verificación de correo

Hasta que el correo no esté verificado, el acceso a documentos, firma y administración está bloqueado (el sistema pide verificación). Las cuentas creadas por un administrador se crean con el correo ya verificado.

---

## 2. Rol Administrador

### 2.1 Qué puede hacer

| Función | Detalle |
|---|---|
| **Crear usuarios** | Crea cuentas con nombre, correo y contraseña, eligiendo el rol: **Administrador** o **Firmante**. |
| **Subir documentos** | Carga uno o varios documentos PDF en una sola operación. |
| **Asignar firmantes** | Asigna de 1 a 7 firmantes a cada documento. Los firmantes deben ser usuarios con rol **Firmante**. |
| **Ver todos los documentos** | Consulta el listado completo de documentos subidos, con el estado de firma (Firmado / Pendiente) y qué firmantes faltan por firmar. |
| **Descargar cualquier documento** | Puede abrir/descargar cualquier documento del sistema, lo tenga asignado o no. |
| **Ver resumen automático** | Cada documento muestra un resumen generado automáticamente de su contenido. |

### 2.2 Qué NO puede hacer

| Limitación | Detalle |
|---|---|
| **Firmar documentos** | El administrador nunca aparece como firmante: la asignación de firmantes solo permite usuarios con rol **Firmante**, por lo que no puede firmar ningún documento. |
| **Crear cuentas de forma pública** | No existe registro abierto; las cuentas se crean desde el panel de administración. |
| **Asignarse a sí mismo como firmante** | Su propio rol no figura en la lista de firmantes disponibles. |
| **Subir archivos que no sean PDF** | Solo se aceptan archivos PDF de hasta 25 MB. |
| **Subir un documento repetido** | El sistema detecta por hash si un documento ya fue subido y lo rechaza. |
| **Asignar más de 7 firmantes** | Límite máximo por documento. |
| **Asignar firmantes duplicados** | Un mismo firmante no puede repetirse en el mismo documento. |

### 2.3 Reglas al subir documentos

- Formatos admitidos: **PDF**.
- Tamaño máximo: **25 MB** por archivo.
- Firma requerida: al menos **1 firmante** por documento.
- Máximo de firmantes por documento: **7**.
- Se pueden subir **varios documentos a la vez**.
- El proceso de almacenamiento es **asíncrono**: tras subir, los documentos se encolan y quedan disponibles para los firmantes una vez procesados (el resumen se genera en ese momento).
- El orden de firma se respeta según el orden en que el administrador selecciona a los firmantes (1.º, 2.º, 3.º, …).

---

## 3. Rol Firmante

### 3.1 Qué puede hacer

| Función | Detalle |
|---|---|
| **Ver sus documentos pendientes** | Ve únicamente los documentos que le han sido asignados y que **puede firmar en este momento**. |
| **Revisar el documento antes de firmar** | Puede abrir/descargar el documento para revisarlo. |
| **Firmar documentos** | Firma los documentos asignados cuando le toque el turno, con confirmación previa. |
| **Saber su turno** | Ve su posición de firma (p. ej. "Firmante 2 de 3") y el número total de firmantes. |
| **Ver el resumen automático** | Cada documento asignado muestra su resumen de contenido. |

### 3.2 Qué NO puede hacer

| Limitación | Detalle |
|---|---|
| **Subir documentos** | La opción de subir documentos solo aparece para administradores. |
| **Crear usuarios** | No puede crear ni modificar cuentas de usuario ni asignar roles. |
| **Acceder a páginas de administración** | Las rutas `admin/*` están bloqueadas para firmantes (respuesta 403). |
| **Ver documentos no asignados** | Solo ve los documentos asignados a él; el resto del catálogo le resulta invisible. |
| **Firmar fuera de su turno** | Debe esperar a que todos los firmantes anteriores hayan firmado. No puede firmar antes de que le toque. |
| **Firmar dos veces** | Una vez firmado, el documento deja de estar disponible para él y no puede volver a firmarlo ni deshacer la firma. |
| **Firmar un documento no asignado** | Aunque adivinara la URL, el sistema rechaza la firma de documentos no asignados (respuesta 403). |

### 3.3 Flujo de firma

1. El administrador sube un documento y le asigna firmantes en un orden.
2. El documento aparece en la lista del **primer firmante** de la cadena.
3. El primer firmante lo revisa, confirma la firma y el documento pasa al siguiente.
4. Cada firmante solo puede firmar cuando **todos los anteriores ya firmaron**.
5. Cuando el último firma, el documento pasa a estado **Firmado** (visible para el administrador).

---

## 4. Comparación rápida de roles

| Capacidad | Administrador | Firmante |
|---|:---:|:---:|
| Iniciar sesión / passkeys / 2FA | ✔ | ✔ |
| Editar perfil, contraseña, eliminar cuenta | ✔ | ✔ |
| Ver Dashboard | ✔ | ✔ |
| Ver listado de documentos | ✔ (todos) | ✔ (solo asignados) |
| Ver resumen del documento | ✔ | ✔ |
| Descargar documento | ✔ (cualquiera) | ✔ (solo asignados) |
| Firmar documentos | ✘ | ✔ (en su turno) |
| Subir documentos | ✔ | ✘ |
| Crear usuarios / asignar roles | ✔ | ✘ |
| Asignar firmantes a documentos | ✔ | ✘ |
| Ver firmantes y estado de cada documento | ✔ | ✘ |
| Ver otros usuarios | Solo para asignarlos | ✘ |

---

## 5. Rutas y accesos protegidos

| Ruta | Método | Acceso |
|---|---|---|
| `/documents` | GET | Ambos roles |
| `/documents/{id}/download` | GET | Administrador (cualquier documento) o firmante asignado |
| `/documents/{id}/sign` | POST | Solo firmante asignado, en su turno y sin firma previa |
| `/admin/users/create`, `/admin/users` | GET/POST | Solo administrador |
| `/admin/documents/create`, `/admin/documents` | GET/POST | Solo administrador |
| `/settings/*` | — | Ambos roles |

> Los intentos de un firmante por acceder a las rutas de administración o firmar documentos no asignados devuelven **403 (Acceso prohibido)**.

---

## 6. Soporte

Si encuentras un problema que no puedes resolver por tu cuenta, envía un correo a **admin-docs@gmail.com** describiendo el inconveniente y recibirás ayuda.
