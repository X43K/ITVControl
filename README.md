# ITVControl

Este proyecto permite gestionar una flota de vehículos, sus citas de ITV, las Estaciones de ITV y los usuarios que pueden acceder al sistema.

## Instrucciones de instalación

1. Descomprime el proyecto en tu servidor local o en un hosting con soporte PHP.
2. Asegúrate de que tu servidor tenga PHP instalado.
3. Coloca todos los archivos en una carpeta en tu servidor.
4. Accede a `'ip-host'/itv/index.php` en tu navegador.
5. Inicia sesión con las credenciales predeterminadas:
   - Usuario: `admin`
   - Contraseña: `admin`
6. A partir de ahí podrás gestionar los vehículos, las citas, las estaciones, imprimir las caducidades y las citas de cada mes, así como los usuarios y su rol.

## Válido para instalar como aplicacion web

## Usuarios de muestra
- usuario: `admin` contraseña: `admin` (con control total)
- usuario: `usuario` contraseña: `usuario` (solo puede consultar)

## Tipos de usuario
- Usuario - Puede consultar e imprimir
- Colaborador - Puede hacer todo lo anterior + añadir citas, añadir vehiculos y modificar estados y caducidades vehiculos.
- Administrador - Puede hacer todo lo anterior + modificar/eliminar citas, eliminar vehiculos y gestionar estaciones.
- SuperAdministrador - Puede hacer todo lo anterior + añadir/modificar/eliminar usuarios.


### SE RECOMIENDA EDITAR O ELIMINAR EL USUARIO `admin`. ANTES DE ELIMINARLO CREE OTRO `SuperAdministrador` PARA PODER SEGUIR GESTIONANDO LOS USUARIOS DEL SISTEMA, YA QUE ES EL UNICO QUE TIENE ESTE PERMISO.

