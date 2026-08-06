# TA Lista de Cotización para WooCommerce

Plugin de WooCommerce que agrega una lista de cotización privada e independiente del carrito.

## v0.1.0 — Hornero

**Hornero** es el primer MVP: pequeño, práctico y construido para validar el flujo esencial antes de ampliar el sistema.

Incluye:

- Botón **Cotizar** independiente de **Agregar al carrito**.
- Lista privada y aislada por sesión de WooCommerce.
- Miniatura, SKU, nombre, descripción corta, cantidad, precio y subtotal.
- Cantidades enteras con valor predeterminado 1.
- Eliminación de productos con opción **Deshacer**.
- Impresión y envío de la solicitud a un vendedor.
- Registro administrativo de cotizaciones enviadas.
- Transferencia masiva al carrito con **Quiero comprar ahora**.
- Estado de transferencia completa o parcial.

## Requisitos

- WordPress 6.4 o posterior.
- PHP 7.4 o posterior.
- WooCommerce 8.0 o posterior.

## Instalación

1. Descargar el ZIP de la versión.
2. En WordPress, abrir **Plugins > Añadir plugin > Subir plugin**.
3. Instalar y activar el plugin.
4. El plugin crea automáticamente la página **Mi cotización**.

## Próxima evolución

La siguiente versión incorporará control de clientes registrados. Cada cliente podrá tener un porcentaje de descuento preferencial acordado, aplicado sobre el precio regular de publicación, sin modificar el precio público general del producto.

También queda previsto:

- Mostrar precio regular, porcentaje asignado y precio preferencial.
- Mantener el descuento individual tanto en la lista como al pasar productos al carrito.
- Definir permisos administrativos para gestionar clientes y descuentos.
- Guardar listas, consultar listas recientes y repetir la última lista en una versión posterior.

## Licencia

GPL-2.0-or-later.
