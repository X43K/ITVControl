# ITVControl
<img src="https://github.com/X43K/ITVControl/blob/c343d1baa2b6a1ef822edc28d58ec07b376d64ba/images/logo.webp">
- Este proyecto permite gestionar una flota de vehículos, sus citas de ITV, las Estaciones de ITV y los usuarios que pueden acceder al sistema.

## Instrucciones de instalación

1. Descarga o clona el repositorio.
2. Sube los archivos a un servidor con PHP 7.4+. No requiere base de datos.
3. Coloca todos los archivos en una carpeta, por ejemplo `/var/www/html/itv/`.
4. Accede a `'ip-host'/itv/index.php` en tu navegador.
5. Inicia sesión con las credenciales predeterminadas:
   - Usuario: `admin`
   - Contraseña: `admin`
6. A partir de ahí podrás gestionar los vehículos, las citas, las estaciones, imprimir las caducidades y las citas de cada mes, así como los usuarios y su rol.

## Válido para instalar como aplicacion web

## Credenciales por defecto
- usuario: `admin` contraseña: `admin` Nivel: SuperAdministrador (Control total)
- usuario: `usuario` contraseña: `usuario` Nivel: Usuario (Consultar e imprimir)

## Tipos de usuario
- Usuario - Puede consultar e imprimir
- Colaborador - Puede hacer todo lo anterior + añadir citas, añadir vehiculos y modificar estados y caducidades vehiculos.
- Administrador - Puede hacer todo lo anterior + modificar/eliminar citas, eliminar vehiculos, gestionar estaciones, ver y desbloquear IPs bloqueadas por intentos de acceso no autorizado a archivos sensibles.
- SuperAdministrador - Puede hacer todo lo anterior + añadir/modificar/eliminar usuarios.

## SEGURIDAD
- Se recomienda editar/eliminar el usuario `admin`. Si decide eliminarlo, antes cree otro `SuperAdministrador` para poder seguir gestionando los usuarios del sistema, ya que es el unico que tiene este permiso.
- Se ha añadido una capa de seguridad para evitar la exposicion de los archivos con datos sesibles `.json` y `.log`. Si es necesario, editar `apache2.conf` -> `sudo nano /etc/apache2/apache2.conf` editando en el bloque `<Directory /var/www/>` la linea `AllowOverride None` por `AllowOverride All`, por ultimo reinicie Apache2 `sudo systemctl restart apache2`. Esto protegera el acceso remoto a los archivos `.json` y `.log` que guardan datos sensibles mediante el uso de `.htaccess` situado en la raiz del proyecto.

<img src="https://github.com/X43K/ITVControl/blob/1a1353d5830b926422414690ca60931f1bb142c8/images/ejemplo1.webp">
<img src="https://github.com/X43K/ITVControl/blob/1a1353d5830b926422414690ca60931f1bb142c8/images/ejemplo2.webp">

## Cómo contribuir
- Reporta errores en la sección Issues.
- Sugiere mejoras.
- Prueba la aplicación y envía feedback.



