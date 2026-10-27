<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Zamanlanmış görevler aynı süreçte çalışır.
 *
 * Laravel'in varsayılanı her komutu Symfony Process ile ayrı bir süreçte
 * başlatmaktır. Bu sunucuda proc_open php.ini'de kapalı olduğu için o yol
 * çalışmıyor: schedule:run her dakika LogicException atıyor ve hiçbir görev
 * yürütülmüyordu (29.08.2026'da 14 saat bot durdu, log 912 MB'a şişti).
 * Artisan::call komutu süreç içinde çalıştırır, proc_open gerektirmez ve
 * bir ea-php güncellemesi php.ini'yi yeniden ezerse de bozulmaz.
 *
 * withoutOverlapping kilidi ada bağlı olduğu için her göreve ad verilir.
 */
$zamanli = function (string $komut) {
    return Schedule::call(fn () => Artisan::call($komut))->name($komut);
};

// Nöbetçi eczane verilerini her gece 23:50'de çek, sonra cache temizle
$zamanli('pharmacy:fetch')->dailyAt('23:50')->after(function () {
    Cache::forget('pharmacy.cities');
    foreach (\App\Models\City::all() as $city) {
        Cache::forget("pharmacy.{$city->id}");
    }
});

// ── Haber Botu ──────────────────────────────────────────────────────────────
// Tarama ve işleme ayrı çalışır: tarama ucuz ve sık, işleme Gemini kotası
// harcadığı için daha seyrek. Yayın her çeyrek saatte sırayı kontrol eder.

// Tarama her turda tüm kaynakları dolaşır ve süre bütçesiyle sınırlanır,
// böylece bir haberin fark edilme gecikmesi en fazla tarama aralığı kadardır.
$zamanli('bot:crawl')->everyThirtyMinutes()->withoutOverlapping(28);
// Editör taramadan sonra, işlemeden önce çalışır: değersiz duyurular Gemini
// kotasını harcamadan havuzda elenir.
$zamanli('bot:curate')->everyThirtyMinutes()->withoutOverlapping(20);
// İşleme yarım saatte bir: günlük taslak limiti maliyeti zaten sınırlıyor,
// sık çalışmak haberin siteye çıkma süresini kısaltır.
$zamanli('bot:process')->everyThirtyMinutes()->withoutOverlapping(28);
// Yayın sık çalışır: akış kipinde her turda sırada haber varsa bir tane çıkar,
// böylece hazır haber saat dilimini beklemez. 5 dakikada bir tur, günlük tavan
// 250 haber; sıra boşsa tur bir şey yapmadan kapanır, maliyeti yoktur.
$zamanli('bot:publish')->everyFiveMinutes()->withoutOverlapping(4);
$zamanli('bot:cleanup')->dailyAt('04:30');

// Belediye başkanı sayfalarını Google'a bir kez bildirir. Günlük kota haberlerle
// paylaşıldığı için sabah erken çalışır; kota yetmezse ertesi gün devam eder.
$zamanli('bot:google-index --baskanlar')->dailyAt('06:15');

// Hava durumu verilerini 6 saatte bir çek (00:00, 06:00, 12:00, 18:00)
$zamanli('weather:fetch')->everySixHours()->after(function () {
    Cache::forget('weather.index.today');
    Cache::forget('weather.cities');
    foreach (\App\Models\City::all() as $city) {
        Cache::forget("weather.{$city->id}");
    }
});
