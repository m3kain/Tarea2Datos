from tables import *
import random

def asignar_topicos(articulos, areas, min_por_articulo=1, max_por_articulo=3):
    relaciones = []
    for articulo in articulos:
        cantidad = random.randint(min_por_articulo, max_por_articulo)
        seleccionadas = random.sample(areas, cantidad)
        for area in seleccionadas:
            relaciones.append(Topico(id_area=area.id_area, id_articulo=articulo.id_articulo))
    return relaciones

def asignar_especializaciones(revisores, areas, min_por_revisor=2, max_por_revisor=4):
    relaciones = []
    for revisor in revisores:
        cantidad = random.randint(min_por_revisor, max_por_revisor)
        seleccionadas = random.sample(areas, cantidad)
        for area in seleccionadas:
            relaciones.append(Especializacion(id_usuario=revisor.id_usuario, id_area=area.id_area))
    return relaciones

def asignar_escribiendo(autores, articulos, autores_por_articulo=2):
    if len(autores) == 0:
        raise ValueError("No hay autores disponibles para asignar a los artículos.")
    relaciones = []
    autores_utilizados = set()
    for articulo in articulos:
        seleccionados = random.sample(autores, autores_por_articulo)
        for i, autor in enumerate(seleccionados):
            relaciones.append(Escribiendo(id_usuario=autor.id_usuario, id_articulo=articulo.id_articulo, autor_contacto=(i == 0)))
            autores_utilizados.add(autor.id_usuario)

            # Si el autor era revisor (subclase 3), cambia a 4
            if autor.subclase == 3:
                autor.subclase = 4  # autor + revisor

    return relaciones


def asignar_formularios(revisores, articulos, escribiendo, revisores_por_articulo=3):
    relaciones = []
    for articulo in articulos:
        # Revisores que NO son autores del artículo
        posibles = [
            r for r in revisores
            if not any(e.id_usuario == r.id_usuario and e.id_articulo == articulo.id_articulo for e in escribiendo)
        ]

        if not posibles:
            continue  # Evita error si no hay revisores disponibles

        seleccionados = random.sample(posibles, min(len(posibles), revisores_por_articulo))
        for revisor in seleccionados:
            formulario = Formulario.create_random(revisor.id_usuario, articulo.id_articulo)
            relaciones.append(formulario)

    return relaciones
