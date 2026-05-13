# SOJ - Green IT: Auditoria y optimizacion eco-tecnologica

## 1) Auditoria teorica de huella de carbono (infraestructura)

### Alcance auditado
- Frontend SPA (Vue + Inertia) servido por Nginx/Apache.
- Backend API Laravel/PHP-FPM.
- Base de datos MySQL.
- CDN/DNS (Cloudflare).
- Correo transaccional SMTP.

### Modelo simplificado utilizado
- Emision por transferencia de datos: 0.06 kWh/GB.
- Factor de emision electrica medio: 0.20 kg CO2e/kWh.
- Formula:
  - Emisiones (kg CO2e) = Trafico (GB) x 0.06 x 0.20
  - Emisiones (kg CO2e) = Trafico (GB) x 0.012

### Ejemplo de estimacion mensual (teorica)
- Trafico web/API estimado: 250 GB/mes.
- Emisiones estimadas: 250 x 0.012 = 3.00 kg CO2e/mes.

### Indicadores recomendados
- GB transferidos/mes (origen + CDN).
- Peso medio por pagina (KB) y por recurso estatico.
- Requests por sesion y tiempo medio de CPU por request.
- Emisiones teoricas mensuales y por 1000 sesiones.

## 2) Plan de sostenibilidad implementado y propuesto

### Medidas ya implementadas en el proyecto
- Modo oscuro global sincronizado entre landing y paneles para reducir consumo en pantallas OLED.
- Compilacion de assets con Vite (minificacion y chunking).
- Carga diferida de componentes pesados (modal de autenticacion async).
- Uso de CDN/DNS para optimizar entrega de estaticos y caches.
- Medicion de CO2 ahorrado por viaje en backend (campo co2_saved).

### Medidas aplicadas en esta iteracion
- Seccion de sostenibilidad en landing con metricas globales de los 3 roles.
- Calculadora de CO2 integrada para comparar uso digital frente a proceso fisico.
- Resumen global con viajes completados, distancia total y CO2 evitado.

### Medidas recomendadas a corto plazo (proxima iteracion)
- Migrar imagenes grandes a WebP/AVIF y servir variantes responsive.
- Definir budget de peso por pagina (ej: <= 250 KB gzip en landing critica).
- Activar cache-control largo para assets versionados.
- Revisar chunks > 500 KB y separar dependencias de alto peso.

### Hosting verde
- Priorizar proveedor con energia 100% renovable certificada.
- Exigir informe anual de PUE y mix energetico.
- Mantener region de despliegue cercana al usuario principal para reducir latencia y consumo.

## 3) Ciclo de vida de hardware y gestion de RAEE

### Politica recomendada de ciclo de vida
- Vida util objetivo de equipos: 4-5 anos.
- Reacondicionamiento interno antes de reemplazar.
- Priorizar compra de hardware reparable y con piezas disponibles.

### Gestion de residuos electronicos (RAEE)
- Inventario de equipos con fecha de compra y estado.
- Borrado seguro de datos antes de retirada.
- Entrega solo a gestores RAEE autorizados.
- Evidencia documental de retirada y reciclaje.

### KPI de seguimiento RAEE
- % equipos reutilizados frente a sustituidos.
- % equipos enviados a gestor autorizado.
- Tiempo medio de vida por tipo de dispositivo.

## 4) Subreto: calculadora de CO2 en la web

### Estado
- Implementada en la landing.
- Combina:
  - Ahorro por movilidad (diferencia coche vs taxi por km).
  - Ahorro por digitalizacion (papel evitado por tramite).
- Se alimenta con datos globales agregados de pasajeros, conductores y admins.

### Supuestos usados en calculos
- Coche: 0.12 kg CO2/km.
- Taxi: 0.08 kg CO2/km.
- Papel A4: 0.0065 kg CO2 por hoja.

## 5) Evidencias tecnicas en codigo
- Resumen global de sostenibilidad: routes/web.php (prop sostenibilidad en la home).
- Visualizacion + calculadora: resources/js/Paginas/Inicio.vue.
- Textos i18n: resources/js/i18n/locales/es.json y resources/js/i18n/locales/en.json.

## 6) Riesgos y limitaciones
- La auditoria es teorica y depende de supuestos energeticos.
- Para precision real se requiere export de consumo de proveedor, logs de trafico y observabilidad de CPU/RAM.
- Factores de emision pueden variar por pais y periodo.

## 7) Siguiente fase recomendada
- Automatizar reporte mensual de huella (trafico + factor de emision vigente).
- Incorporar dashboard admin con tendencias de CO2.
- Publicar politica de sostenibilidad anual con objetivos medibles.
