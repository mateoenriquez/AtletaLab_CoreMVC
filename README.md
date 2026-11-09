# AtletaLab

Panel web para administrar la planificación de progresión biomecánica y descarga muscular. Centraliza las cargas, las rutinas y los registros de entrenamiento que alimentan el análisis de fatiga y la evaluación de cada bloque de trabajo.

[AtletaLab en Alwaysdata](https://mateo-enriquez.alwaysdata.net/)

## Funcionalidad

- Ocho módulos CRUD para usuarios, roles, arquetipos de progresión, objetivos, ejercicios, rutinas, asignaciones y registros de entrenamiento.
- Dashboard con indicadores de actividad, estado de las rutinas y mejores prácticas históricas.
- Selectores dependientes de grupo muscular, arquetipo y rutina que consultan endpoints JSON con `fetch`.

## Arquitectura

La aplicación usa PHP 8.1+, CakePHP 5.2 y MySQL/MariaDB. Sigue el patrón MVC con el ORM de CakePHP, plantillas PHP y JavaScript nativo.

- El esquema relacional aplica claves foráneas y borra en cascada las asignaciones y sus registros al eliminar una rutina. [Esquema SQL](config/schema/atletalab.sql)
- Los modelos validan la precisión de las cargas, los límites de fatiga y de esfuerzo percibido (RPE), la coherencia de las repeticiones y los roles de las personas asignadas a cada rutina.
- El panel exige una sesión con rol de administrador. Usa CakePHP Authentication 3, hash de contraseñas con bcrypt, protección CSRF y un límite de intentos de inicio de sesión.

## Desarrollo

Composer instala las dependencias fijadas en `composer.lock`. La configuración local se basa en [app_local.example.php](config/app_local.example.php) y los valores privados se guardan en `config/app_local.php`.

`composer check` verifica la sintaxis PHP y ejecuta las pruebas de modelos y rutas HTTP con SQLite en memoria.

Licencia [MIT](LICENSE.txt).
