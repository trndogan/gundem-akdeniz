<?php

/**
 * Akdeniz Bölgesi resmî kurum aday listesi üreteci.
 *
 * Çıktı: bot-detect.php'nin okuduğu aday JSON'u.
 *   - url  verilmişse doğrudan o adres denenir (ISAY gibi şablonu bilinen kurumlar)
 *   - site verilmişse haber sayfası otomatik keşfedilir
 *
 * Kullanım: php tools/akdeniz-candidates.php > tools/candidates.json
 */

/** Türkçe adı alan adı parçasına çevirir */
function slug(string $s): string
{
    $s = strtr(mb_strtolower($s, 'UTF-8'), [
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i̇' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a',
    ]);

    return preg_replace('/[^a-z0-9]/', '', $s) ?? $s;
}

// ── Akdeniz Bölgesi: 8 il ve ilçeleri ───────────────────────────────────────
$iller = [
    'Adana' => ['Seyhan', 'Yüreğir', 'Çukurova', 'Sarıçam', 'Ceyhan', 'Kozan', 'İmamoğlu', 'Karataş', 'Karaisalı', 'Pozantı', 'Aladağ', 'Feke', 'Saimbeyli', 'Tufanbeyli', 'Yumurtalık'],
    'Antalya' => ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Aksu', 'Döşemealtı', 'Alanya', 'Manavgat', 'Serik', 'Kumluca', 'Kaş', 'Kemer', 'Korkuteli', 'Elmalı', 'Finike', 'Demre', 'Gazipaşa', 'Akseki', 'Gündoğmuş', 'İbradı'],
    'Burdur' => ['Bucak', 'Gölhisar', 'Tefenni', 'Yeşilova', 'Karamanlı', 'Çavdır', 'Ağlasun', 'Altınyayla', 'Çeltikçi'],
    'Hatay' => ['Antakya', 'Defne', 'Samandağ', 'İskenderun', 'Arsuz', 'Dörtyol', 'Payas', 'Erzin', 'Kırıkhan', 'Reyhanlı', 'Altınözü', 'Yayladağı', 'Belen', 'Hassa', 'Kumlu'],
    'Isparta' => ['Yalvaç', 'Şarkikaraağaç', 'Eğirdir', 'Gelendost', 'Senirkent', 'Uluborlu', 'Keçiborlu', 'Gönen', 'Atabey', 'Sütçüler', 'Yenişarbademli'],
    'Kahramanmaraş' => ['Onikişubat', 'Dulkadiroğlu', 'Elbistan', 'Afşin', 'Göksun', 'Pazarcık', 'Türkoğlu', 'Andırın', 'Çağlayancerit', 'Ekinözü', 'Nurhak'],
    'Mersin' => ['Akdeniz', 'Toroslar', 'Yenişehir', 'Mezitli', 'Tarsus', 'Erdemli', 'Silifke', 'Anamur', 'Mut', 'Bozyazı', 'Gülnar', 'Aydıncık', 'Çamlıyayla'],
    'Osmaniye' => ['Kadirli', 'Düziçi', 'Bahçe', 'Toprakkale', 'Sumbas', 'Hasanbeyli'],
];

/** Aynı ada sahip ilçeler birden fazla ilde var; alan adı hangisine ait belirsiz.
 *  Bunlarda şehir ataması sayfa başlığından doğrulanır (city_guess = null bırakılır). */
$ambiguous = ['aksu', 'kemer', 'yenisehir', 'gonen', 'merkez', 'akdeniz', 'karatas'];

$out = [];
$add = function (array $row) use (&$out) { $out[] = $row; };

// ── 1) Valilikler (İçişleri ISAY şablonu — /haberler sabit) ─────────────────
foreach (array_keys($iller) as $il) {
    $add([
        'name' => "{$il} Valiliği",
        'url' => 'https://www.' . slug($il) . '.gov.tr/haberler',
        'site' => 'https://www.' . slug($il) . '.gov.tr',
        'institution_type' => 'valilik',
        'city' => $il,
    ]);
}

// ── 2) Kaymakamlıklar (ISAY şablonu) ────────────────────────────────────────
foreach ($iller as $il => $ilceler) {
    foreach ($ilceler as $ilce) {
        $s = slug($ilce);
        $add([
            'name' => "{$ilce} Kaymakamlığı",
            'url' => "https://www.{$s}.gov.tr/haberler",
            'site' => "https://www.{$s}.gov.tr",
            'institution_type' => 'kaymakamlik',
            'city' => in_array($s, $ambiguous, true) ? null : $il,
        ]);
    }
}

// ── 3) İl belediyeleri ──────────────────────────────────────────────────────
$ilBelediye = [
    'Adana' => ['Adana Büyükşehir Belediyesi', 'https://www.adana.bel.tr'],
    'Antalya' => ['Antalya Büyükşehir Belediyesi', 'https://www.antalya.bel.tr'],
    'Burdur' => ['Burdur Belediyesi', 'https://www.burdur.bel.tr'],
    'Hatay' => ['Hatay Büyükşehir Belediyesi', 'https://www.hatay.bel.tr'],
    'Isparta' => ['Isparta Belediyesi', 'https://www.isparta.bel.tr'],
    'Kahramanmaraş' => ['Kahramanmaraş Büyükşehir Belediyesi', 'https://www.kahramanmaras.bel.tr'],
    'Mersin' => ['Mersin Büyükşehir Belediyesi', 'https://www.mersin.bel.tr'],
    'Osmaniye' => ['Osmaniye Belediyesi', 'https://www.osmaniye.bel.tr'],
];
foreach ($ilBelediye as $il => [$name, $site]) {
    $add(['name' => $name, 'site' => $site, 'institution_type' => 'belediye', 'city' => $il]);
}

// ── 4) İlçe belediyeleri ────────────────────────────────────────────────────
foreach ($iller as $il => $ilceler) {
    foreach ($ilceler as $ilce) {
        $s = slug($ilce);
        $add([
            'name' => "{$ilce} Belediyesi",
            'site' => "https://www.{$s}.bel.tr",
            'institution_type' => 'belediye',
            'city' => in_array($s, $ambiguous, true) ? null : $il,
        ]);
    }
}

// ── 5) İl müdürlükleri ──────────────────────────────────────────────────────
$mudurlukler = [
    ['İl Sağlık Müdürlüğü', 'https://{s}ism.saglik.gov.tr', 'Sağlık'],
    ['İl Tarım ve Orman Müdürlüğü', 'https://{s}.tarimorman.gov.tr', 'Tarım'],
    ['İl Millî Eğitim Müdürlüğü', 'https://{s}.meb.gov.tr', 'Eğitim'],
    ['İl Kültür ve Turizm Müdürlüğü', 'https://{s}.ktb.gov.tr', 'Kültür Sanat'],
    ['Çevre, Şehircilik ve İklim Değişikliği İl Müdürlüğü', 'https://{s}.csb.gov.tr', 'Gündem'],
    ['İl AFAD Müdürlüğü', 'https://{s}.afad.gov.tr', 'Gündem'],
    ['İl Emniyet Müdürlüğü', 'https://{s}.pol.tr', 'Asayiş'],
    ['Gençlik ve Spor İl Müdürlüğü', 'https://{s}.gsb.gov.tr', 'Spor'],
];
foreach (array_keys($iller) as $il) {
    $s = slug($il);
    foreach ($mudurlukler as [$label, $tpl, $cat]) {
        $add([
            'name' => "{$il} {$label}",
            'site' => str_replace('{s}', $s, $tpl),
            'institution_type' => 'il_mudurlugu',
            'city' => $il,
            'category' => $cat,
        ]);
    }
}

// ── 6) Üniversiteler ────────────────────────────────────────────────────────
$universiteler = [
    ['Akdeniz Üniversitesi', 'https://www.akdeniz.edu.tr', 'Antalya'],
    ['Alanya Alaaddin Keykubat Üniversitesi', 'https://www.alanya.edu.tr', 'Antalya'],
    ['Antalya Belek Üniversitesi', 'https://www.belek.edu.tr', 'Antalya'],
    ['Çukurova Üniversitesi', 'https://www.cu.edu.tr', 'Adana'],
    ['Adana Alparslan Türkeş Bilim ve Teknoloji Üniversitesi', 'https://www.atu.edu.tr', 'Adana'],
    ['Mersin Üniversitesi', 'https://www.mersin.edu.tr', 'Mersin'],
    ['Tarsus Üniversitesi', 'https://www.tarsus.edu.tr', 'Mersin'],
    ['Hatay Mustafa Kemal Üniversitesi', 'https://www.mku.edu.tr', 'Hatay'],
    ['İskenderun Teknik Üniversitesi', 'https://www.iste.edu.tr', 'Hatay'],
    ['Süleyman Demirel Üniversitesi', 'https://www.sdu.edu.tr', 'Isparta'],
    ['Isparta Uygulamalı Bilimler Üniversitesi', 'https://www.isparta.edu.tr', 'Isparta'],
    ['Burdur Mehmet Akif Ersoy Üniversitesi', 'https://www.mehmetakif.edu.tr', 'Burdur'],
    ['Kahramanmaraş Sütçü İmam Üniversitesi', 'https://www.ksu.edu.tr', 'Kahramanmaraş'],
    ['Kahramanmaraş İstiklal Üniversitesi', 'https://www.istiklal.edu.tr', 'Kahramanmaraş'],
    ['Osmaniye Korkut Ata Üniversitesi', 'https://www.osmaniye.edu.tr', 'Osmaniye'],
];
foreach ($universiteler as [$name, $site, $il]) {
    $add(['name' => $name, 'site' => $site, 'institution_type' => 'universite', 'city' => $il, 'category' => 'Eğitim']);
}

// ── 7) Kalkınma ajansları ───────────────────────────────────────────────────
$ajanslar = [
    ['Batı Akdeniz Kalkınma Ajansı (BAKA)', 'https://www.baka.gov.tr', 'Antalya'],
    ['Çukurova Kalkınma Ajansı (ÇKA)', 'https://www.cka.gov.tr', 'Adana'],
    ['Doğu Akdeniz Kalkınma Ajansı (DOĞAKA)', 'https://www.dogaka.gov.tr', 'Hatay'],
];
foreach ($ajanslar as [$name, $site, $il]) {
    $add(['name' => $name, 'site' => $site, 'institution_type' => 'kalkinma_ajansi', 'city' => $il, 'category' => 'Ekonomi']);
}

// ── 8) Kamu kurumu niteliğindeki meslek kuruluşları (oda ve borsalar) ───────
$odalar = [
    ['Antalya Ticaret ve Sanayi Odası (ATSO)', 'https://www.atso.org.tr', 'Antalya'],
    ['Antalya Ticaret Borsası', 'https://www.antalyatb.org.tr', 'Antalya'],
    ['Alanya Ticaret ve Sanayi Odası (ALTSO)', 'https://www.altso.org.tr', 'Antalya'],
    ['Adana Ticaret Odası', 'https://www.adanato.org.tr', 'Adana'],
    ['Adana Sanayi Odası (ADASO)', 'https://www.adaso.org.tr', 'Adana'],
    ['Adana Ticaret Borsası', 'https://www.adanatb.org.tr', 'Adana'],
    ['Mersin Ticaret ve Sanayi Odası (MTSO)', 'https://www.mtso.org.tr', 'Mersin'],
    ['Tarsus Ticaret ve Sanayi Odası', 'https://www.tarsustso.org.tr', 'Mersin'],
    ['Hatay Ticaret ve Sanayi Odası', 'https://www.hatayto.org.tr', 'Hatay'],
    ['İskenderun Ticaret ve Sanayi Odası', 'https://www.itso.org.tr', 'Hatay'],
    ['Kahramanmaraş Ticaret ve Sanayi Odası', 'https://www.kmtso.org.tr', 'Kahramanmaraş'],
    ['Isparta Ticaret ve Sanayi Odası', 'https://www.isparta-tso.org.tr', 'Isparta'],
    ['Burdur Ticaret ve Sanayi Odası', 'https://www.burdurtso.org.tr', 'Burdur'],
    ['Osmaniye Ticaret ve Sanayi Odası', 'https://www.osmaniyetso.org.tr', 'Osmaniye'],
];
foreach ($odalar as [$name, $site, $il]) {
    $add(['name' => $name, 'site' => $site, 'institution_type' => 'oda_borsa', 'city' => $il, 'category' => 'Ekonomi']);
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
