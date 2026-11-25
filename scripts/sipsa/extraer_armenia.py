"""Extrae las filas de un municipio del anexo SIPSA de precios mayoristas (DANE) a CSV.

Uso: python3 -I scripts/sipsa/extraer_armenia.py anex-SIPSA-SerieHistoricaMayorista-2026.xlsx \
        backend/database/data/sipsa/sipsa-mayorista-armenia-2026.csv [MUNICIPIO]

Solo usa la biblioteca estándar (el .xlsx es un ZIP con XML). No transforma los datos: conserva
grupo, producto, código CPC, mercado y el precio tal como los publica el DANE; la fecha (serial de
Excel) se convierte a AAAA-MM-DD. La traducción al modelo de la plataforma (unidad base, categoría,
vigencia) la hace la capa anticorrupción del contexto Ingesta (TraductorSipsa).
"""
import csv, datetime, re, sys, zipfile
import xml.etree.ElementTree as ET

NS = '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
COLUMNAS = {'A': 'fecha', 'B': 'grupo', 'C': 'producto', 'D': 'codigo_cpc', 'E': 'mercado', 'H': 'municipio', 'J': 'precio'}


def filas(libro: zipfile.ZipFile):
    compartidas = [''.join(t.text or '' for t in si.iter(NS + 't'))
                   for si in ET.fromstring(libro.read('xl/sharedStrings.xml')).iter(NS + 'si')]
    for fila in ET.fromstring(libro.read('xl/worksheets/sheet1.xml')).iter(NS + 'row'):
        valores = {}
        for celda in fila.iter(NS + 'c'):
            v = celda.find(NS + 'v')
            if v is not None:
                col = re.match(r'[A-Z]+', celda.get('r')).group()
                valores[col] = compartidas[int(v.text)] if celda.get('t') == 's' else v.text
        yield valores


def main(origen: str, destino: str, municipio: str = 'ARMENIA') -> None:
    with zipfile.ZipFile(origen) as libro, open(destino, 'w', newline='', encoding='utf-8') as salida:
        escritor = csv.writer(salida)
        escritor.writerow(['fecha', 'grupo', 'producto', 'codigo_cpc', 'mercado', 'precio'])
        n = 0
        for f in filas(libro):
            if f.get('H') != municipio or not (f.get('A') or '').isdigit():
                continue
            fecha = datetime.date(1899, 12, 30) + datetime.timedelta(days=int(f['A']))
            escritor.writerow([fecha.isoformat(), f['B'], f['C'], f['D'], f['E'], f['J']])
            n += 1
    print(f'{n} filas de {municipio} → {destino}')


if __name__ == '__main__':
    main(*sys.argv[1:])
