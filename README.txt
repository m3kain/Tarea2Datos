Martin Ignacio Garcia Valdes 202373068-8
Emiio Fidel Tapia Victoriano 202373093-9

#Instrucciones para Poblar la Base de Datos

1. Ejecutar el script CREATE.sql en pgAdmin para crear las tablas.
2. Correr INSERT/main.py desde terminal para generar los datos aleatorios:
   python INSERT/main.py


sudo /opt/lampp/lampp start
sudo /opt/lampp/lampp stop

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE formulario;
TRUNCATE TABLE escribiendo;
TRUNCATE TABLE topicos;
TRUNCATE TABLE articulo;
TRUNCATE TABLE especializacion;
TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;

Por si no deja crear function:
$sudo /opt/lampp/bin/mysql_upgrade -u root -p --socket=/opt/lampp/var/mysql/mysql.sock --skip-version-check



