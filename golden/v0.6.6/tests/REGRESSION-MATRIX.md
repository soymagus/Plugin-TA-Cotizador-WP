# Regression Matrix — v0.6.6 Golden Baseline

Toda versión futura debe marcar PASS en cada fila aplicable antes de publicarse como estable.

| ID | Área | Escenario | Resultado esperado |
|---|---|---|---|
| R01 | Instalación | Instalar ZIP compatible | WordPress reconoce y activa sin fatal errors |
| R02 | Upgrade | Actualizar desde v0.6.6 | Configuración y solicitudes existentes permanecen |
| R03 | Configuración | Guardar una solapa | Las otras solapas conservan sus valores |
| R04 | Producto | Añadir al carrito nativo | Compra WooCommerce sigue funcionando |
| R05 | Producto | Cotizar producto | Producto entra a lista privada |
| R06 | Sesión | Dos sesiones anónimas | Listas aisladas entre navegadores |
| R07 | Lista | Actualizar cantidad | Cantidad/totales correctos |
| R08 | Lista | Eliminar individual | Sólo se elimina el producto elegido |
| R09 | Lista | Eliminar masivo | Sólo seleccionados se eliminan |
| R10 | Lista | Deshacer | Restaura última eliminación soportada |
| R11 | Lista | Drag & drop | Orden persiste y numeración se actualiza |
| R12 | Lista | Agregar más productos | Abre shop/catálogo y conserva lista |
| R13 | Envío | Enviar consulta | Crea un único request con cliente/mensaje/items |
| R14 | Envío | Reintento duplicado | Acción comercial duplicada se bloquea según reglas |
| R15 | Compra | Comprar ahora | Transfiere items válidos al carrito una vez |
| R16 | Historial | Entrada inicial | Vista compacta, no todas las listas abiertas |
| R17 | Historial | Buscar por ID/fecha | Devuelve solicitudes correctas |
| R18 | Historial | Expandir/contraer | Sólo detalle seleccionado se abre/cierra correctamente |
| R19 | Historial | Miniatura/mensaje | Snapshot y mensaje original visibles |
| R20 | Estado | Estado visible | Estado actual en historial y admin |
| R21 | PDF | Descargar | Documento comercial con campos configurados y acentos correctos |
| R22 | Imprimir | Histórico | Imprime sólo la solicitud seleccionada |
| R23 | Imprimir | Lista activa | Usa el mismo modelo de datos/configuración que PDF |
| R24 | Share | Email | Abre sin reemplazar la pantalla de historial/lista |
| R25 | Share | Redes | Facebook/LinkedIn/Instagram/WhatsApp/Copiar funcionan según config |
| R26 | Recuperación | Solicitar código | Código enviado por capa de correo configurada |
| R27 | Recuperación | Credenciales válidas | Consulta recuperable desde navegador sin cookies originales |
| R28 | Recuperación | Credenciales inválidas | Consulta privada no se expone |
| R29 | Email | WordPress mail | Resultado registrado y errores trazables |
| R30 | Email | SMTP propio | Test/consulta/recuperación salen por SMTP configurado |
| R31 | Admin | Abrir solicitud | Muestra cliente, productos, mensaje, estado y detalle |
| R32 | Admin | Responder | Registra traza y estado Respondida |
| R33 | Admin | Derivar | Incluye URL segura y actualiza traza/estado |
| R34 | Admin | Agregar producto en Nueva | Producto agregado y evento trazado |
| R35 | Clonar | Estado permitido | Nueva copia editable, original intacta |
| R36 | Clonar | Estado no permitido | Acción no disponible/rechazada |
| R37 | Clonar | Referencia visual | Respeta texto/color original/copia configurados |
| R38 | CAPTCHA | Interno | Respuesta inválida bloquea envío |
| R39 | CAPTCHA | Google | Token se valida server-side |
| R40 | CAPTCHA | Turnstile | Token se valida server-side |
| R41 | Backup | Export local | Configuración/solicitudes/logs se exportan |
| R42 | Backup | ZIP + PDF | Incluye PDFs documentales cuando está habilitado |
| R43 | Backup | Restaurar | Backup compatible restaura sin fatal error |
| R44 | Backup | Google Drive | Destino conectado recibe backup |
| R45 | Backup | S3 compatible | Endpoint/bucket configurado recibe backup |
| R46 | Reportes | Períodos | Usa límites de fecha de timezone WordPress |
| R47 | Timezone | Fecha/hora visible | Coincide con Ajustes > Generales de WordPress |
| R48 | Responsive | Notebook | Usa ancho configurado y acciones alineadas |
| R49 | Responsive | Mobile | Controles se adaptan sin overflow |
| R50 | Visual | Botones | Visibles sin hover y respetan estilos configurados |
| R51 | Personalización | CSS custom | Persiste y carga después del CSS del plugin |
| R52 | Personalización | Celdas | HTML seguro/shortcodes aparecen en posiciones configuradas |
