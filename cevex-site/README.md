# CEVEX International — Sitio web

Sitio web corporativo de **CEVEX International S.A.C.**, empresa organizadora de
congresos, conferencias y experiencias formativas (desde 2016) y creadora del
**Congreso Internacional de Autores**.

Es un sitio **estático** (HTML, CSS y JavaScript, sin dependencias ni build):
basta abrir `index.html` o servir la carpeta con cualquier servidor estático.

## Estructura

```
cevex-site/
├── index.html                 # Página única (one-page)
└── assets/
    ├── css/styles.css         # Estilos
    ├── js/main.js             # Navegación, animaciones, cuenta regresiva, formulario
    └── img/
        ├── cevex-mark.svg     # Isotipo (degradado)
        ├── cevex-mark-white.svg
        └── photos/            # Fotos reales (ver photos/README.md)
```

## Identidad

- **Tipografía:** Fraunces (titulares) + Inter (texto), vía Google Fonts.
- **Color:** blanco, azul corporativo (`#1e5bd6`), negro tinta (`#0c1424`) y
  degradado azul→violeta (`#2ba8e0 → #1e5bd6 → #4b2e97`).
- **Tono:** sobrio, elegante y cercano; orientado a transmitir trayectoria y confianza.

## Secciones

Encabezado, Quiénes somos (misión / visión), Experiencia y cifras, Servicios,
Congreso Internacional de Autores, Ponentes, Próxima edición (con cuenta
regresiva), Galería y testimonios, Alianzas y Contacto.

## Ver en local

```bash
cd cevex-site
python3 -m http.server 8080
# abre http://localhost:8080
```

## Notas

- El formulario de contacto valida en el navegador y abre el cliente de correo
  (`mailto:info@cevex.org`). Para envío automático se puede conectar luego a un
  backend o servicio de formularios.
- Las fotos son opcionales: si faltan, el sitio usa respaldos en degradado y no
  se ve incompleto. Ver `assets/img/photos/README.md`.

## Datos de contacto

- Teléfono: +51 968 648 574
- Correo: info@cevex.org
- Instagram: [@cevexinternacional](https://www.instagram.com/cevexinternacional/) ·
  [@congresoautores](https://www.instagram.com/congresoautores/)
