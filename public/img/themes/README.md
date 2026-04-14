# Decoraciones Temáticas

Este directorio contiene las decoraciones SVG para los diferentes temas de la aplicación.

## Estructura

```
themes/
├── halloween/
│   ├── spiderweb.svg    (Telaraña - usado en header y footer izquierdo)
│   └── spider.svg       (Araña - usado en sidebar y footer derecho)
├── dia-muertos/
│   ├── flower.svg       (Flor de Cempasúchil - usado en header y footers)
│   └── altar.svg        (Altar de muertos - usado en sidebar)
└── navidad/
    ├── tree.svg         (Árbol de Navidad - usado en header y footer derecho)
    ├── gift.svg         (Regalo - usado en sidebar)
    └── reindeer.svg     (Reno - usado en footer izquierdo)
```

## Ubicación de las Decoraciones

### Header
- Aparece al lado del nombre "SIMS"
- Tamaño: 24px (móvil) / 32px (escritorio)

### Sidebar
- Aparece en la parte inferior después de los menús
- Tamaño: 80px de ancho
- Centrado horizontalmente

### Footer
- Aparece en los extremos izquierdo y derecho
- Tamaño: 48px de ancho
- Solo visible en pantallas medianas y grandes (md:block)
- Centrado verticalmente

## Formato Recomendado

- **Formato**: SVG (escalable, ligero, personalizable)
- **Dimensiones recomendadas**: 
  - Header: 60-100px
  - Sidebar: 80-120px
  - Footer: 40-60px

## Personalización

Para reemplazar las decoraciones por tus propios diseños:

1. Crea o exporta tu SVG
2. Guárdalo en la carpeta correspondiente del tema
3. Usa el mismo nombre de archivo para reemplazar automáticamente
4. Asegúrate de que el SVG tenga un viewBox apropiado para escalar correctamente

## Temas Disponibles

1. **Halloween**: Telarañas, arañas, colores oscuros
2. **Día de Muertos**: Flores de cempasúchil, altares, colores vibrantes
3. **Navidad**: Árboles, regalos, renos, colores festivos
4. **Institucional**: Sin decoraciones (tema por defecto)

## Configuración en el Código

Las rutas de las decoraciones se configuran en:
- `resources/js/stores/themeStore.js` en el computed `themeDecorations`

Para agregar un nuevo tema con decoraciones:
1. Crea una nueva carpeta en `public/img/themes/[nombre-tema]/`
2. Agrega tus SVGs
3. Actualiza el computed `themeDecorations` en themeStore.js

