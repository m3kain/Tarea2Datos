from faker import Faker
import random

fake = Faker()



class Users:
    def __init__(self, id_usuario, nombre, email, password, subclase):
        self.id_usuario = id_usuario
        self.nombre = nombre
        self.email = email
        self.password = password
        self.subclase = subclase

    @classmethod
    def create_random(cls, id_usuario, subclase):
        nombre = fake.name()
        email = f"{nombre.lower().replace(' ', '')}{id_usuario}@mail.com"
        password = f"clave{random.randint(1000,9999)}"
        return cls(id_usuario, nombre, email, password, subclase)

    def to_insert(self):
        return f"INSERT INTO usuarios (id_usuario, nombre, email, password, subclase) VALUES ({self.id_usuario}, '{self.nombre}', '{self.email}', '{self.password}', {self.subclase});"



class Area:
    def __init__(self, id_area, titulo_area):
        self.id_area = id_area
        self.titulo_area = titulo_area

    @classmethod
    def create_random(cls, id_area):
        titulo = fake.word().capitalize()
        return cls(id_area, titulo)

    def to_insert(self):
        return f"INSERT INTO area (id_area, titulo_area) VALUES ({self.id_area}, '{self.titulo_area}');"


class Articulo:
    def __init__(self, id_articulo, titulo, resumen,fecha_envio, fecha_limite_modificacion, aceptacion=False):
        self.id_articulo = id_articulo
        self.titulo = titulo
        self.resumen = resumen
        self.fecha_envio = fecha_envio
        self.fecha_limite_modificacion = fecha_limite_modificacion
        self.aceptacion = aceptacion

    @classmethod
    def create_random(cls, id_articulo):
        titulo = fake.sentence(nb_words=6).replace("'", "")
        resumen = fake.text(max_nb_chars=100).replace("'", "")
        fecha = "2025-06-01"
        return cls(id_articulo, titulo, resumen, fecha)
        
    def to_insert(self):
        acept = "NULL" if self.aceptacion is None else int(self.aceptacion)
        return f"""INSERT INTO articulo 
        (id_articulo, titulo, resumen, fecha_envio, fecha_limite_modificacion, aceptacion)
        VALUES ({self.id_articulo}, '{self.titulo}', '{self.resumen}', '{self.fecha_envio}', '{self.fecha_limite_modificacion}', {acept});"""


class Topico:
    def __init__(self, id_area, id_articulo):
        self.id_area = id_area
        self.id_articulo = id_articulo

    @classmethod
    def create_random(cls, id_area, id_articulo):
        return cls(id_area, id_articulo)

    def to_insert(self):
        return f"INSERT INTO topicos (id_area, id_articulo) VALUES ({self.id_area}, {self.id_articulo});"

class Especializacion:
    def __init__(self, id_usuario, id_area):
        self.id_usuario = id_usuario
        self.id_area = id_area

    @classmethod
    def create_random(cls, id_usuario, id_area):
        return cls(id_usuario, id_area)

    def to_insert(self):
        return f"INSERT INTO especializacion (id_usuario, id_area) VALUES ({self.id_usuario}, {self.id_area});"

class Escribiendo:
    def __init__(self, id_usuario, id_articulo, autor_contacto=False):
        self.id_usuario = id_usuario
        self.id_articulo = id_articulo
        self.autor_contacto = autor_contacto

    @classmethod
    def create_random(cls, id_usuario, id_articulo, autor_contacto=False):
        return cls(id_usuario, id_articulo, autor_contacto)

    def to_insert(self):
        return f"INSERT INTO escribiendo (id_usuario, id_articulo, autor_contacto) VALUES ({self.id_usuario}, {self.id_articulo}, {str(self.autor_contacto).upper()});"


class Formulario:
    def __init__(self, id_usuario, id_articulo, calidad_tecnica, originalidad, valoracion_global, argumentosvg, comentarios_autores):
        self.id_usuario = id_usuario
        self.id_articulo = id_articulo
        self.calidad_tecnica = calidad_tecnica
        self.originalidad = originalidad
        self.valoracion_global = valoracion_global
        self.argumentosvg = argumentosvg
        self.comentarios_autores = comentarios_autores

    @classmethod
    def create_random(cls, id_usuario, id_articulo):
        return cls(
            id_usuario,
            id_articulo,
            calidad_tecnica=random.randint(1, 10),
            originalidad=random.choice([True, False]),
            valoracion_global=random.randint(1, 10),
            argumentosvg=fake.sentence(),
            comentarios_autores=fake.sentence()
        )

    def to_insert(self):
        return (
            f"INSERT INTO formulario (id_usuario, id_articulo, calidad_tecnica, originalidad, valoracion_global, argumentosvg, comentarios_autores) "
            f"VALUES ({self.id_usuario}, {self.id_articulo}, {self.calidad_tecnica}, {str(self.originalidad).upper()}, {self.valoracion_global}, '{self.argumentosvg}', '{self.comentarios_autores}');"
        )