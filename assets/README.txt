=====================================================
  LuxWrap Studio - Correcciones Aplicadas
  Fecha: 20 de julio de 2026
=====================================================

PROBLEMA 1: MENÚ MÓVIL CON FONDO TRANSPARENTE (RESUELTO)
---------------------------------------------------------
Causa: El menú móvil usaba background: rgba(10,10,15,0.98)
con backdrop-filter: blur(20px). En muchos navegadores móviles
el backdrop-filter no se soporta correctamente, dejando el
fondo semi-transparente y el texto sobrepuesto al contenido.

Solución aplicada:
- Clase CSS: .nav-links.mobile-open (dentro de @media max-width: 992px)
- background: #000000 !important (negro sólido, 100% opaco)
- backdrop-filter: none (eliminado para evitar conflictos)
- width: 100vw, height: 100vh (cobertura total de pantalla)
- opacity: 1 !important (previene cualquier transparencia heredada)
- z-index: 1001 (asegura que esté encima de todo el contenido)

PROBLEMA 2: IMÁGENES VOLTEADAS/ROTADAS (RESUELTO)
--------------------------------------------------
9 imágenes estaban rotadas 90° en sentido anti-horario.
Se corrigieron rotándolas 90° en sentido horario.

Imágenes corregidas:
1. portfolio-02-angels-roofing-truck-rear.jpg
2. portfolio-04-angels-roofing-truck-hood.jpg
3. portfolio-05-angels-roofing-truck-front.jpg
4. portfolio-06-angels-roofing-truck-full.jpg
5. portfolio-07-angels-roofing-roof-wrap.jpg
6. portfolio-08-angels-roofing-roof-flag.jpg
7. portfolio-09-angels-roofing-truck-back.jpg
8. portfolio-16-dulce-salado-van-front.jpg
9. portfolio-17-dulce-salado-van-rear.jpg

Las demás imágenes del portafolio ya estaban correctamente
orientadas y no requirieron cambios.

ARCHIVOS MODIFICADOS:
---------------------
- index.html (CSS del menú móvil corregido)
- 9 imágenes de portafolio (orientación corregida)

INSTRUCCIONES DE DESPLIEGUE:
----------------------------
1. Reemplazar todos los archivos en el servidor
2. Limpiar caché del navegador (Ctrl+Shift+R) para ver cambios
3. Probar el menú móvil en un dispositivo real
4. Verificar las imágenes del portafolio
