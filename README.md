# Gündem Akdeniz - gundemakdeniz.com

Akdeniz Bölgesi'nin öncü haber platformu. Laravel 11 + Filament PHP v3 + Livewire 3 mimarisi.

## Teknoloji Yığını

- **Framework:** Laravel 11
- **Admin Panel:** Filament PHP v3
- **Frontend:** Blade + Livewire 3 + Alpine.js + Tailwind CSS v4
- **Görsel Optimizasyon:** Spatie Media Library (otomatik WebP dönüşümü)
- **SEO:** JSON-LD NewsArticle şeması, dinamik sitemap.xml
- **Cache:** Redis entegrasyonu (production)
- **Queue:** Laravel Queue (database/redis)

## Gereksinimler

- PHP 8.3+
- Composer
- Node.js 18+
- SQLite (geliştirme) / MySQL 8+ (production)
- Redis (production, opsiyonel geliştirme)

## Kurulum (Geliştirme)

```bash
# Bağımlılıkları kur
composer install
npm install

# .env dosyasını oluştur
cp .env.example .env
php artisan key:generate

# Veritabanını oluştur ve örnek verileri yükle
php artisan migrate --seed

# Asset'leri derle
npm run build

# Sunucuyu başlat
php artisan serve
```

**Admin Panel:** http://localhost:8000/admin
- **E-posta:** admin@gundemakdeniz.com
- **Şifre:** password

## Production Kurulumu (AlmaLinux 9 / cPanel)

### 1. .env Yapılandırması
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gundemakdeniz.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gundemakdeniz
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

### 2. Deploy Komutları
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 3. Queue Worker (Supervisor)
```ini
[program:gundemakdeniz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/user/public_html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/home/user/logs/worker.log
```

### 4. Cron Job (cPanel)
```
* * * * * cd /home/user/public_html && php artisan schedule:run >> /dev/null 2>&1
```

## Proje Yapısı

```
app/
├── Filament/Resources/     # Admin panel kaynakları
│   ├── ArticleResource     # Haber yönetimi
│   ├── CategoryResource    # Kategori yönetimi
│   ├── CityResource        # Şehir yönetimi
│   ├── BreakingNewsResource# Son dakika yönetimi
│   └── AdSlotResource      # Reklam alanları
├── Http/Controllers/       # Web controller'lar
├── Jobs/                   # Queue job'ları
├── Livewire/               # Livewire component'leri
│   ├── CityNews            # Şehir bazlı haber sekmesi
│   └── SearchBox           # Canlı arama
└── Models/                 # Eloquent modeller

resources/views/
├── layouts/app.blade.php   # Ana layout
├── home.blade.php          # Ana sayfa
├── article/                # Haber, kategori, şehir sayfaları
├── livewire/               # Livewire view'ları
└── partials/               # Header, footer, breaking-news
```

## Özellikler

- **Manşet & Öne Çıkan Haberler:** Bento grid tasarım
- **Şehir Bazlı Haberler:** Livewire ile SPA-benzeri sekme geçişleri
- **Son Dakika Bandı:** Marquee animasyonlu kayan haber bandı
- **Canlı Arama:** Livewire debounce ile anlık arama
- **SEO:** JSON-LD NewsArticle, Open Graph, Twitter Card, dinamik sitemap
- **Görsel Optimizasyon:** Otomatik WebP, responsive srcset
- **Redis Cache:** Ana sayfa blokları ve sık sorgulanan haberler
- **Queue:** Görsel işleme ve bot entegrasyonu arka planda
