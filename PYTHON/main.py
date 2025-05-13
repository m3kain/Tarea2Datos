from faker import Faker
import random
from tables import *
from relations import *
import os 
from dotenv import load_dotenv
load_dotenv()
import mysql.connector
from datetime import date
from collections import defaultdict



conn = mysql.connector.connect(
    database=os.getenv("DB_NAME"),
    user=os.getenv("DB_USER"),
    password=os.getenv("DB_PASSWORD"),
    host=os.getenv("DB_HOST"),
    port=3306
)
cur = conn.cursor()

fake = Faker("es_CL")
random.seed(42)

tablas = [
    "formulario", "escribiendo", "especializacion", "topicos",
    "articulo", "area", "usuarios"
]
for tabla in tablas:
    cur.execute(f"DELETE FROM {tabla};")
conn.commit()


def generar_usuarios():
    usuarios = []

    # Agregar jefe de comité con id_usuario = 1 y subclase = 1
    usuarios.append(Users.create_random(id_usuario=1, subclase=1))

    id_usuario = 2

    # Asegurar al menos 20 autores
    for _ in range(20):
        usuarios.append(Users.create_random(id_usuario, subclase=2))
        id_usuario += 1

    # Asegurar al menos 30 revisores
    for _ in range(30):
        usuarios.append(Users.create_random(id_usuario, subclase=3))
        id_usuario += 1


  
    while id_usuario <= 100:
        subclase = random.choice([2, 3, 4])  # 2 = autor, 3 = revisor, 4 = ambos
        usuarios.append(Users.create_random(id_usuario, subclase=subclase))
        id_usuario += 1

    return usuarios

def generar_areas():
    temas = [
        "Inteligencia Artificial", "Ciberseguridad", "Algoritmos", "Redes",
        "Álgebra", "Estadística", "Geología", "Biología", "Química", "Física",
        "Idiomas", "Literatura", "Lingüística", "Historia", "Geografía",
        "Ética", "Filosofía", "Derecho"
    ]
    return [Area(i + 1, tema) for i, tema in enumerate(temas)]

def generar_articulos():
    articulos = []
    for i in range(1, 401):
        aceptacion = random.choice([None, 0, 1])  # None = en proceso
        titulo = fake.sentence(nb_words=6).replace("'", "")
        resumen = fake.text(max_nb_chars=100).replace("'", "")
        fecha_envio = fake.date_between(start_date=date(2019, 1, 1), end_date=date(2025, 1, 1))
        fecha_limite_modificacion = fake.date_between(start_date=fecha_envio, end_date=date(2025, 7, 1))
        articulos.append(Articulo(i, titulo, resumen, fecha_envio, fecha_limite_modificacion, aceptacion))
    return articulos



if __name__ == "__main__":
    usuarios = generar_usuarios()

    # Separar según subclase
    autores = [u for u in usuarios if u.subclase in (2, 4)]
    revisores = [u for u in usuarios if u.subclase in (3, 4)]
    articulos = generar_articulos()
    areas = generar_areas()
    topicos = asignar_topicos(articulos, areas)
    especializaciones = asignar_especializaciones(revisores, areas)
    print(f"Total autores generados: {len(autores)}")

    escribiendo = asignar_escribiendo(autores, articulos)
    formularios = asignar_formularios(revisores, articulos, escribiendo)


    # Agrupar formularios por artículo
    evaluaciones = defaultdict(list)
    for f in formularios:
        evaluaciones[f.id_articulo].append(f)

    # Aplicar lógica de aceptación
    for articulo in articulos:
        if articulo.id_articulo in evaluaciones:
            valores = evaluaciones[articulo.id_articulo]
            prom_val_global = sum(f.valoracion_global for f in valores) / len(valores)
            prom_calidad = sum(f.calidad_tecnica for f in valores) / len(valores)
            
            articulo.aceptacion = int(prom_val_global >= 5 and prom_calidad > 5)
        else:
            articulo.aceptacion = None  # Sin evaluaciones, sigue NULL


    # Insertar usuarios
    for usuario in usuarios:
        cur.execute(usuario.to_insert())


    # Insertar el resto de las tablas
    otras_tablas = [articulos, areas, topicos, especializaciones, escribiendo, formularios]
    for tabla in otras_tablas:
        for fila in tabla:
            query = fila.to_insert()
            cur.execute(query)

    conn.commit()
    conn.close()