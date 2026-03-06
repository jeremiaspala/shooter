# RemindMe - Sistema de Recordatorios por Email
<img width="1893" height="846" alt="shooter1" src="https://github.com/user-attachments/assets/03956680-582f-47aa-899b-6fdd52b2b87b" />
<img width="1910" height="879" alt="shooter2" src="https://github.com/user-attachments/assets/fdf4c92e-ccd6-4bdc-89a9-9bdd885d3ce0" />
<img width="1897" height="839" alt="shooter3" src="https://github.com/user-attachments/assets/2caf0301-99e1-4886-a199-9f51de41914c" />

## Requisitos

- PHP 7.4+
- MySQL 5.7+
- Composer
- Extensiones PHP: pdo, pdo_mysql, mbstring

## Instalación

### 1. Ejecutar el instalador

Accede al instalador desde tu navegador:

```
http://tu-servidor/install.php
```

### 2. Configuración de base de datos

El instalador creará automáticamente la base de datos y las tablas necesarias.

**Credenciales por defecto:**
- Admin: `admin` / `admin123`
- Visor: `visor` / `visor123`

### 3. Instalar dependencias

```bash
cd /var/www/html/shooter
composer install
```

## Configuración del Cron

Para que los recordatorios se envíen automáticamente, necesitas configurar el crontab.

### Opción 1: Crontab del usuario www-data (recomendado)

```bash
# Agregar al crontab de www-data
sudo crontab -u www-data -l 2>/dev/null
echo "* * * * * php /var/www/html/shooter/cron/send_reminders.php >> /var/log/shooter_reminders.log 2>&1" | sudo crontab -u www-data -
```

### Opción 2: Archivo en /etc/cron.d/

```bash
sudo tee /etc/cron.d/shooter > /dev/null << 'EOF'
* * * * * www-data php /var/www/html/shooter/cron/send_reminders.php >> /var/log/shooter_reminders.log 2>&1
EOF
sudo chmod 644 /etc/cron.d/shooter
```

### Opción 3: Crontab manual

```bash
# Abrir editor de crontab
crontab -e

# Agregar esta línea:
* * * * * php /var/www/html/shooter/cron/send_reminders.php >> /var/log/reminders.log 2>&1
```

### Verificar que el cron funciona

```bash
# Ver los logs
tail -f /var/log/shooter_reminders.log

# Probar el script manualmente
php /var/www/html/shooter/cron/send_reminders.php

# Verificar estado del servicio cron
sudo systemctl status cron
```

## Estructura de Roles

- **Admin**: Acceso completo (crear/editar/eliminar recordatorios, usuarios, SMTP)
- **Visor**: Solo lectura (ver recordatorios y estadísticas)

## Características

### Recordatorios
- Editor WYSIWYG para el contenido
- Múltiples destinatarios
- Repetición:
  - Una vez
  - Diario
  - Semanal (días seleccionados)
  - Mensual (días del mes)
  - Anual
  - Personalizado (intervalo configurable)
- Condiciones de fin:
  - Nunca
  - Después de X ejecuciones
  - En fecha específica

### Configuración SMTP
- Servidor, puerto, usuario, contraseña
- Cifrado: TLS, SSL, ninguno
- Nombre y email del remitente
- Prueba de configuración

## Solución de Problemas

### Error de conexión a base de datos
Verificar que las credenciales en `config/database.php` son correctas.

### No se envían los emails
1. Verificar que hay una configuración SMTP activa
2. Revisar los logs de errores en `/var/log/reminders.log`
3. Verificar que el cron está ejecutándose: `crontab -l`

### Error de PHPMailer
```bash
composer install
```

Asegúrate de que vendor/autoload.php existe.
