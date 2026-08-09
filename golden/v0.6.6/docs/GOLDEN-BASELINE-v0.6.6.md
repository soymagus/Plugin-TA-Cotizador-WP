# TA Cotizador de Productos v0.6.6 "Hornero" — Golden Baseline

## Estado

Este documento congela la v0.6.6 como referencia de regresión y reconstrucción para futuras versiones.

**Regla de oro:** ninguna versión posterior puede quitar, alterar, degradar visualmente o cambiar un comportamiento documentado aquí salvo que el requerimiento de esa versión autorice explícitamente ese cambio.

Una versión es compatible con esta baseline solamente cuando pasa la matriz completa de regresión de `tests/REGRESSION-MATRIX.md`.

## Artefacto inmutable

- Versión: `0.6.6`
- Producto: `TA Cotizador de Productos`
- Nombre de versión: `Hornero`
- Artefacto instalable: `artifacts/ta-cotizador-productos-0.6.6-hornero-timezone-hotfix.zip`
- SHA-256: `29c5f1269b708b104820344869dfd268fa65274a13c08e012e37786b1d1d5cac`
- Carpeta/slug interno preservado por compatibilidad: `ta-quote-list`

## Garantías de baseline

1. WooCommerce conserva la compra nativa y agrega Cotizar en producto y catálogo.
2. Cada visitante, incluso anónimo, mantiene una lista aislada mediante sesión de WooCommerce.
3. La lista admite agregar, cambiar cantidad, eliminar, eliminar masivamente, deshacer, reordenar drag & drop y transferir al carrito.
4. Se conservan los shortcodes `[ta_quote_list]` y `[ta_quote_history]`.
5. El plugin crea/utiliza páginas dedicadas para Mi cotización e Historial y dispone de template full-width propio.
6. Las solicitudes enviadas se almacenan como `taql_quote` con snapshot de productos, datos del cliente, mensaje, estado, recuperación/share y trazabilidad.
7. Los Request ID siguen el esquema humano con prefijo `HOR-`.
8. Historial compacto/buscable, expandible, con PDF, impresión, Share y clonación cuando corresponde.
9. PDF e Imprimir usan el mismo modelo comercial configurable: logo, cabecera, empresa, cliente, mensaje, productos, miniaturas, precios, estado y pie.
10. Correo por WordPress o SMTP propio. Recuperación y comunicaciones usan la misma capa de email.
11. Seguridad con nonces, sanitización/escape, honeypot y proveedor elegible: desafío interno, Google reCAPTCHA o Cloudflare Turnstile.
12. Share It preserva Email, Facebook, LinkedIn, Instagram, WhatsApp y Copiar URL según configuración.
13. Administración permite abrir detalle, cambiar estado, responder, derivar y agregar productos mientras la solicitud sea elegible.
14. Responder/derivar actualiza estado y trazabilidad.
15. Clonar se gobierna por estados configurables y mantiene relación original/copia.
16. Backup conserva configuración, solicitudes, logs y PDFs documentales, con descarga local y destinos Google Drive/S3 compatible cuando están configurados.
17. Reportes por períodos.
18. Todas las fechas/horas visibles usan la zona horaria configurada en WordPress (`wp_timezone`, `wp_date`, `current_time`); el firmado S3 mantiene UTC por requerimiento del protocolo.
19. Guardar una solapa de configuración nunca puede borrar valores de otras solapas.
20. El lenguaje visual, responsive, colores configurables, botones individuales, CSS personalizado, celdas de contenido y layout de acciones del producto forman parte del baseline.

## Contrato de compatibilidad

No renombrar opciones persistidas, meta keys, shortcodes, post type, acciones, IDs de páginas ni carpeta interna sin una migración explícita. Los datos ya guardados son autoridad y deben seguir siendo compatibles.

## Criterio de reconstrucción

Una reconstrucción se considera equivalente solamente si:

- instala y pasa todos los criterios de aceptación;
- puede importar un backup de v0.6.6 sin pérdida material de datos;
- reproduce los flujos principales de frontend y administración;
- mantiene paridad PDF/Imprimir;
- mantiene persistencia de configuración y zona horaria de WordPress;
- la matriz de regresión queda 100% PASS.
