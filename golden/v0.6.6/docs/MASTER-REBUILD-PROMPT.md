# Master Rebuild Prompt — TA Cotizador de Productos v0.6.6 "Hornero"

Actuá como Arquitecto de Software Senior y desarrollador Senior de WordPress/WooCommerce/PHP. Reconstruí o extendé **TA Cotizador de Productos** usando **v0.6.6 "Hornero" Golden Baseline** como referencia contractual.

## Regla no negociable

No eliminar, rediseñar, renombrar, simplificar, reemplazar ni alterar comportamientos existentes salvo que el nuevo requerimiento lo pida explícitamente. Preservar UX de frontend, UX de administración, persistencia, shortcodes, sesión/listas, PDF/impresión, historial, recuperación, SMTP, CAPTCHA, Share It, clonación, backup/reportes, responsive, colores/configuración y manejo horario de WordPress.

## Prioridad de fuentes

1. Artefacto exacto v0.6.6 y paquete de preservación en `golden/v0.6.6/artifacts/`.
2. `docs/GOLDEN-BASELINE-v0.6.6.md`.
3. Especificaciones incluidas dentro del paquete Golden completo.
4. `tests/REGRESSION-MATRIX.md`.

Si un cambio pedido entra en conflicto con la baseline, modificar únicamente lo explícitamente solicitado y preservar todo lo demás.

## Flujo obligatorio de ingeniería

Antes de tocar código:

1. Identificar archivos/funciones afectados.
2. Identificar comportamientos baseline en riesgo.
3. Hacer el cambio compatible más pequeño posible.
4. Mantener nombres de opciones/meta y migrar cuando sea necesario.
5. Validar sintaxis PHP y JavaScript.
6. Ejecutar la matriz completa de regresión, no sólo pruebas de la nueva función.
7. Empaquetar con `ta-quote-list/` como carpeta de plugin dentro del ZIP.
8. Verificar actualización desde el ZIP Golden sin pérdida de configuración, solicitudes, históricos ni recuperación.

## Reglas de producto que deben sobrevivir una reconstrucción

- WooCommerce conserva compra nativa junto a Cotizar.
- Listas privadas por sesión incluso para anónimos.
- Solicitudes históricas conservan snapshot y no se reescriben silenciosamente con datos actuales del producto.
- Lista activa e histórico son flujos separados.
- PDF e Imprimir derivan del mismo modelo documental configurable.
- Historial es compacto/buscable y no una columna infinita de solicitudes abiertas.
- Email/share no deben sacar innecesariamente al usuario de la vista actual.
- Request ID solo no concede acceso privado.
- Respuestas, derivaciones y cambios administrativos quedan trazados.
- Fecha/hora visible usa timezone de WordPress.
- Guardar una solapa nunca destruye otras configuraciones.
- Secretos no se muestran ni registran en claro.

## Definition of Done

Una build no está terminada porque instala. Está terminada únicamente cuando pasa todos los escenarios aplicables de la Golden Baseline y se verifica el delta solicitado.
