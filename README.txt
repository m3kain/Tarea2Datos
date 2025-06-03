Martin Ignacio Garcia Valdes 202373068-8
Emiio Fidel Tapia Victoriano 202373093-9


#Instrucciones para inicializar la Base de Datos

1. Tener XAMPP instalado,
2. Instalar los requirements: 
   python3 -m pip install -r requirements.txt,
2.1. Si se esta en Linux se crea entorno virtual:
      sudo apt install python3-venv
      python3 -m venv venv
      source venv/bin/activate
      pip install -r requirements.txt

3. Iniciar y detener servidor MyphpAdmin y:
      sudo /opt/lampp/lampp start
      sudo /opt/lampp/lampp stop
4. Crear la base de datos "sistema_congreso"   
5. Ejecutar el script CREATE.sql en MyphpAdmin para crear las tablas   
6. Si no deja crear function:
      sudo /opt/lampp/bin/mysql_upgrade -u root --socket=/opt/lampp/var/mysql/mysql.sock --skip-version-check
      $sudo /opt/lampp/bin/mysql_upgrade -u root -p --socket=/opt/lampp/var/mysql/mysql.sock --skip-version-check

7. Se necesita mover el proyecto hacia el htdocs de XAMPP, en mi caso:
   sudo mv ~/(Proyecto) /opt/lampp/htdocs/
8. Correr desde PYTHON main.py en la terminal para generar los datos aleatorios,
9. Acceder a:
      http://localhost/Proyecto/PHP/vistas/login.php



-Truncar las tablas:
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE formulario;
TRUNCATE TABLE escribiendo;
TRUNCATE TABLE topicos;
TRUNCATE TABLE articulo;
TRUNCATE TABLE especializacion;
TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;


Supuestos:
- Todo usuario se debe loguear para poder crear un articulo, si se envia un articulo con un autor no
existente se crea este usuario con contraseña '1234' y se manda  un correo a su email
- Si se quiere eliminar la cuenta del jefe debe existir otro jefe
- Los articulos en revision no se pueden ver en la barra de busqueda para darle proposito a los revisores.
- Las vistas del revisor solo permiten la gestion para tener mayor claridad visual
- La tabla formulario con sus atributos en null es un articulo en revision
- Subclases Jefe, Autor, Revisor, Autor y Revisors[1,2,3,4]
- Solo calidad_tecnica, valoracion_global y originalidad son claves para evaluar si una revisión está completa


