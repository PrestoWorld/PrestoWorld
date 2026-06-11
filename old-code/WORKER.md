# Unified Worker Configuration

This project uses a **unified worker** (`worker.php`) that automatically detects and runs on different runtime environments.

## Quick Start

### Auto-Detection (Recommended)

```bash
# Automatically detects available runtime
php worker.php
```

Detection priority:
1. RoadRunner (if RR_MODE env is set)
2. OpenSwoole extension (if installed)
3. Swoole extension (if installed)
4. ReactPHP (if react/http is installed)

### Manual Runtime Selection

```bash
# Force ReactPHP
php worker.php --reactphp

# Force Swoole
php worker.php --swoole

# Force OpenSwoole
php worker.php --openswoole

# Force RoadRunner (use with ./rr serve)
php worker.php --roadrunner
```

### Custom Host and Port

```bash
# Custom port
php worker.php --port=9000

# Custom host
php worker.php --host=127.0.0.1

# Both
php worker.php --host=0.0.0.0 --port=8080
```

## Installation

### ReactPHP
```bash
composer require react/http react/event-loop
php worker.php --reactphp
```

### Swoole
```bash
pecl install swoole
echo "extension=swoole.so" >> /etc/php/8.2/cli/php.ini
php worker.php --swoole
```

### OpenSwoole
```bash
pecl install openswoole
echo "extension=openswoole.so" >> /etc/php/8.2/cli/php.ini
php worker.php --openswoole
```

### RoadRunner
```bash
composer require spiral/roadrunner-http
./vendor/bin/rr get-binary
./rr serve
```

## Default Configuration

- **Host**: `0.0.0.0` (all interfaces)
- **Port**: `8080`
- **Workers**: CPU cores × 2 (for Swoole/OpenSwoole)
- **Coroutines**: Enabled (for Swoole/OpenSwoole)
- **Max Requests**: 10,000 per worker (for Swoole/OpenSwoole)

## Environment Variables

You can also control the runtime via environment variables:

```bash
# Force ReactPHP
REACTPHP_MODE=true php worker.php

# RoadRunner (automatically detected)
RR_MODE=http php worker.php
```

## Production Deployment

### Systemd Service

Create `/etc/systemd/system/witals-worker.service`:

```ini
[Unit]
Description=Witals Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/witals.com
ExecStart=/usr/bin/php /var/www/witals.com/worker.php --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable witals-worker
sudo systemctl start witals-worker
sudo systemctl status witals-worker
```

### Docker

```dockerfile
FROM php:8.2-cli

# Install Swoole
RUN pecl install swoole && docker-php-ext-enable swoole

WORKDIR /app
COPY . /app

EXPOSE 8080

CMD ["php", "worker.php", "--port=8080"]
```

### Nginx Reverse Proxy

```nginx
upstream witals_backend {
    server 127.0.0.1:8080;
}

server {
    listen 80;
    server_name example.com;

    location / {
        proxy_pass http://witals_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Monitoring

### Check if worker is running
```bash
# Check process
ps aux | grep worker.php

# Check port
lsof -i :8080

# Test connection
curl http://localhost:8080
```

### View logs
```bash
# Systemd logs
sudo journalctl -u witals-worker -f

# Docker logs
docker logs -f witals-container
```

## Troubleshooting

### Port already in use
```bash
# Find process using port
lsof -i :8080

# Kill process
kill -9 <PID>

# Or use different port
php worker.php --port=9000
```

### Extension not found
```bash
# Check installed extensions
php -m | grep swoole
php -m | grep openswoole

# Check PHP version
php -v

# Verify php.ini
php --ini
```

### Memory issues
```bash
# Increase memory limit
php -d memory_limit=512M worker.php

# Monitor memory
watch -n 1 'ps aux | grep worker.php'
```

## Performance Tuning

### Swoole/OpenSwoole Options

Edit `worker.php` to customize:

```php
$server = new SwooleServer($app, $host, $port, [
    'worker_num' => 8,              // Number of workers
    'max_request' => 10000,         // Restart worker after N requests
    'enable_coroutine' => true,     // Enable coroutines
    'max_coroutine' => 100000,      // Max concurrent coroutines
    'max_wait_time' => 60,          // Max wait time before shutdown
    'reload_async' => true,         // Async reload
    'max_connection' => 10000,      // Max connections
]);
```

### ReactPHP Options

ReactPHP uses event loop, no worker configuration needed.

## Comparison

| Feature | ReactPHP | Swoole | OpenSwoole | RoadRunner |
|---------|----------|--------|------------|------------|
| Auto-detect | ✅ | ✅ | ✅ | ✅ |
| Single port | ✅ | ✅ | ✅ | ✅ |
| Async I/O | ✅ | ✅ | ✅ | ❌ |
| Coroutines | ❌ | ✅ | ✅ | ❌ |
| Workers | ❌ | ✅ | ✅ | ✅ |
| Setup | Easy | Medium | Medium | Medium |
| Performance | 8x | 15x | 15x | 10x |

## Next Steps

1. Choose your runtime
2. Install dependencies
3. Run `php worker.php`
4. Test with `curl http://localhost:8080`
5. Deploy to production with systemd or Docker
