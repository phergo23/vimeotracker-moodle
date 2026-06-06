# VimeoTracker para Moodle

VimeoTracker es un módulo personalizado para Moodle diseñado para realizar el seguimiento del tiempo de visualización de videos de Vimeo por parte de los estudiantes y automatizar la generación de reportes ejecutivos.

## 🚀 Características
* **Seguimiento en tiempo real:** Registra la posición de reproducción de cada alumno por video.
* **Motor de búsqueda inteligente:** Localiza videos insertados en Libros, Páginas y Etiquetas de Moodle automáticamente.
* **Reportes Automatizados:** Generación de reportes en Excel o HTML bajo demanda.
* **Integración con Microsoft 365:** Envío de correos mediante Microsoft Graph API para una entrega profesional.

## 🛠️ Requisitos de Configuración
Para que el módulo funcione correctamente, requiere:
1. **Azure App Registration:** Debe tener configurado el permiso `Mail.Send` como *Application Permission*.
2. **Consentimiento:** Debe otorgarse el "Admin Consent" en el portal de Azure.
3. **Credenciales:** Configurar el `tenant_id`, `client_id` y `client_secret` en el archivo `reporte.php`.

## ⚙️ Instalación
1. Copie la carpeta `vimeotracker` dentro del directorio `/filter/` de su instalación de Moodle.
2. Ejecute la limpieza de cachés en Moodle: 
   `php /ruta/a/moodle/admin/cli/purge_caches.php`

## 📊 Uso del Sistema
El reporte se genera mediante una petición URL. Asegúrese de reemplazar los valores entre corchetes:

`https://tu-sitio-moodle.com/filter/vimeotracker/reporte.php?id=[ID_CURSO]&formato=[excel/pdf]&email=[CORREO_DESTINO]&token=[TOKEN]

* **ID_CURSO:** ID numérico del curso en Moodle.
* **Formato:** `excel` (descarga archivo) o `pdf` (visualización web).
* **Token:** Llave de seguridad (`TOKEN`).

## 🤝 Contribuciones
Siéntase libre de realizar *forks* o enviar *pull requests* para mejorar las funcionalidades de seguimiento.

---
*Desarrollado para Advance Learning.*
