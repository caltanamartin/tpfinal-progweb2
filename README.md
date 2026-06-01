# PreguntaTres

Juego de preguntas y respuestas en plataforma web.

## Requisitos

- Apache 2.4+ con `mod_rewrite` habilitado
- PHP 8.0+ con extensión `mysqli`
- MariaDB 10+ o MySQL 8+

## Instalación

### 1. Clonar el proyecto en el servidor web

```bash
git clone <repo> /var/www/html
```

### 2. Configurar Apache

Habilitar `mod_rewrite` y permitir `.htaccess`:

```bash
sudo a2enmod rewrite
```

Editar `/etc/apache2/apache2.conf` y cambiar `AllowOverride None` por `AllowOverride All` dentro del bloque `<Directory /var/www/>`:

```
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Reiniciar Apache:

```bash
sudo systemctl restart apache2
```

### 3. Crear la base de datos

```bash
mysql -u root < database.sql
```

Si la base ya existe, corregir el error de `CREATE SCHEMA` y ejecutar el resto manualmente, o simplemente:

```bash
mysql -u root --force < database.sql
```

### 4. Configurar conexión

Editar `config/config.ini` con los datos de tu base de datos:

```ini
hostname='localhost'
username= 'root'
password= ''
database= 'aldea_vikinga'
```

### 5. Abrir en el navegador

```
http://localhost/
```

## Rutas disponibles

| URL | Descripción |
|---|---|
| `/` | Landing / bienvenida |
| `/login` | Inicio de sesión |
| `/registro` | Registro de usuario |
