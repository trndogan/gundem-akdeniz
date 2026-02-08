export interface District {
  name: string;
  lat: number;
  lon: number;
}

export interface WeatherCity {
  label: string;
  slug: string;
  lat: number;
  lon: number;
  districts: District[];
}

export const WEATHER_CITIES: WeatherCity[] = [
  {
    label: 'Antalya', slug: 'antalya', lat: 36.8969, lon: 30.7133,
    districts: [
      { name: 'Muratpaşa', lat: 36.8850, lon: 30.7040 },
      { name: 'Konyaaltı', lat: 36.8693, lon: 30.6370 },
      { name: 'Kepez', lat: 36.9350, lon: 30.6900 },
      { name: 'Aksu', lat: 36.9470, lon: 30.8330 },
      { name: 'Döşemealtı', lat: 37.0600, lon: 30.5900 },
      { name: 'Alanya', lat: 36.5440, lon: 31.9990 },
      { name: 'Manavgat', lat: 36.7870, lon: 31.4430 },
      { name: 'Serik', lat: 36.9170, lon: 31.1000 },
      { name: 'Kaş', lat: 36.2000, lon: 29.6380 },
      { name: 'Kemer', lat: 36.5980, lon: 30.5590 },
      { name: 'Kumluca', lat: 36.3710, lon: 30.2900 },
      { name: 'Finike', lat: 36.2990, lon: 30.1480 },
      { name: 'Demre', lat: 36.2430, lon: 29.9870 },
      { name: 'Gazipaşa', lat: 36.2680, lon: 32.3140 },
      { name: 'Elmalı', lat: 36.7380, lon: 29.9170 },
      { name: 'Korkuteli', lat: 37.0670, lon: 30.1960 },
      { name: 'Akseki', lat: 37.0490, lon: 31.7900 },
      { name: 'Gündoğmuş', lat: 36.8120, lon: 32.0100 },
      { name: 'İbradı', lat: 37.0960, lon: 31.5980 },
    ],
  },
  {
    label: 'Mersin', slug: 'mersin', lat: 36.8121, lon: 34.6415,
    districts: [
      { name: 'Akdeniz', lat: 36.8000, lon: 34.6100 },
      { name: 'Mezitli', lat: 36.7630, lon: 34.5250 },
      { name: 'Yenişehir', lat: 36.8100, lon: 34.6300 },
      { name: 'Toroslar', lat: 36.8600, lon: 34.6200 },
      { name: 'Tarsus', lat: 36.9160, lon: 34.8930 },
      { name: 'Silifke', lat: 36.3770, lon: 33.9340 },
      { name: 'Erdemli', lat: 36.6100, lon: 34.3100 },
      { name: 'Anamur', lat: 36.0770, lon: 32.8380 },
      { name: 'Mut', lat: 36.6440, lon: 33.4380 },
      { name: 'Gülnar', lat: 36.3750, lon: 33.3900 },
      { name: 'Aydıncık', lat: 36.1470, lon: 33.3200 },
      { name: 'Bozyazı', lat: 36.1020, lon: 32.9700 },
      { name: 'Çamlıyayla', lat: 37.1700, lon: 34.6000 },
    ],
  },
  {
    label: 'Adana', slug: 'adana', lat: 37.0000, lon: 35.3213,
    districts: [
      { name: 'Seyhan', lat: 36.9900, lon: 35.3300 },
      { name: 'Çukurova', lat: 37.0100, lon: 35.3800 },
      { name: 'Yüreğir', lat: 37.0000, lon: 35.3900 },
      { name: 'Sarıçam', lat: 37.0600, lon: 35.4200 },
      { name: 'Karaisalı', lat: 37.2500, lon: 35.0600 },
      { name: 'Ceyhan', lat: 37.0300, lon: 35.8100 },
      { name: 'Kozan', lat: 37.4560, lon: 35.8150 },
      { name: 'İmamoğlu', lat: 37.2900, lon: 35.6700 },
      { name: 'Karataş', lat: 36.5700, lon: 35.3800 },
      { name: 'Pozantı', lat: 37.4300, lon: 34.8700 },
      { name: 'Tufanbeyli', lat: 38.2700, lon: 36.2200 },
      { name: 'Aladağ', lat: 37.5500, lon: 35.4000 },
      { name: 'Feke', lat: 37.8200, lon: 35.9100 },
      { name: 'Saimbeyli', lat: 37.9800, lon: 36.1000 },
      { name: 'Yumurtalık', lat: 36.7700, lon: 35.7900 },
    ],
  },
  {
    label: 'Hatay', slug: 'hatay', lat: 36.4018, lon: 36.3498,
    districts: [
      { name: 'Antakya', lat: 36.2000, lon: 36.1600 },
      { name: 'İskenderun', lat: 36.5900, lon: 36.1700 },
      { name: 'Defne', lat: 36.2200, lon: 36.1500 },
      { name: 'Dörtyol', lat: 36.8500, lon: 36.2200 },
      { name: 'Samandağ', lat: 36.0800, lon: 35.9800 },
      { name: 'Kırıkhan', lat: 36.5000, lon: 36.3600 },
      { name: 'Reyhanlı', lat: 36.2700, lon: 36.5700 },
      { name: 'Arsuz', lat: 36.4100, lon: 35.8900 },
      { name: 'Payas', lat: 36.7600, lon: 36.2300 },
      { name: 'Erzin', lat: 36.9500, lon: 36.2000 },
      { name: 'Belen', lat: 36.4900, lon: 36.1900 },
      { name: 'Altınözü', lat: 36.1100, lon: 36.2500 },
      { name: 'Hassa', lat: 36.8000, lon: 36.5200 },
      { name: 'Kumlu', lat: 36.3700, lon: 36.4700 },
      { name: 'Yayladağı', lat: 35.9000, lon: 36.0600 },
    ],
  },
  {
    label: 'Isparta', slug: 'isparta', lat: 37.7648, lon: 30.5566,
    districts: [
      { name: 'Merkez', lat: 37.7648, lon: 30.5566 },
      { name: 'Eğirdir', lat: 37.8900, lon: 30.8600 },
      { name: 'Yalvaç', lat: 38.2900, lon: 31.1800 },
      { name: 'Şarkikaraağaç', lat: 38.0800, lon: 31.3700 },
      { name: 'Gelendost', lat: 38.1200, lon: 30.9300 },
      { name: 'Senirkent', lat: 38.1000, lon: 30.5500 },
      { name: 'Keçiborlu', lat: 37.6400, lon: 30.2900 },
      { name: 'Atabey', lat: 37.8800, lon: 30.6400 },
      { name: 'Gönen', lat: 37.9800, lon: 30.5400 },
      { name: 'Uluborlu', lat: 38.0700, lon: 30.4600 },
      { name: 'Sütçüler', lat: 37.4900, lon: 30.9900 },
      { name: 'Aksu', lat: 37.7700, lon: 31.1800 },
      { name: 'Yenişarbademli', lat: 37.6800, lon: 31.3500 },
    ],
  },
  {
    label: 'Burdur', slug: 'burdur', lat: 37.7203, lon: 30.2903,
    districts: [
      { name: 'Merkez', lat: 37.7203, lon: 30.2903 },
      { name: 'Bucak', lat: 37.4590, lon: 30.5950 },
      { name: 'Gölhisar', lat: 37.3700, lon: 29.5100 },
      { name: 'Tefenni', lat: 37.3200, lon: 29.7800 },
      { name: 'Yeşilova', lat: 37.4900, lon: 29.7600 },
      { name: 'Ağlasun', lat: 37.6500, lon: 30.5300 },
      { name: 'Çavdır', lat: 37.1600, lon: 29.6900 },
      { name: 'Altınyayla', lat: 37.2300, lon: 29.5300 },
      { name: 'Çeltikçi', lat: 37.5100, lon: 30.4800 },
      { name: 'Karamanlı', lat: 37.3700, lon: 29.8200 },
      { name: 'Kemer', lat: 37.3800, lon: 30.0600 },
    ],
  },
  {
    label: 'Osmaniye', slug: 'osmaniye', lat: 37.0746, lon: 36.2464,
    districts: [
      { name: 'Merkez', lat: 37.0746, lon: 36.2464 },
      { name: 'Kadirli', lat: 37.3700, lon: 36.1000 },
      { name: 'Düziçi', lat: 37.2800, lon: 36.4700 },
      { name: 'Bahçe', lat: 37.2000, lon: 36.5800 },
      { name: 'Toprakkale', lat: 37.0700, lon: 36.1500 },
      { name: 'Hasanbeyli', lat: 37.1300, lon: 36.5500 },
      { name: 'Sumbas', lat: 37.4400, lon: 36.3200 },
    ],
  },
  {
    label: 'Kahramanmaraş', slug: 'kahramanmaras', lat: 37.5858, lon: 36.9371,
    districts: [
      { name: 'Onikişubat', lat: 37.5858, lon: 36.9371 },
      { name: 'Dulkadiroğlu', lat: 37.5700, lon: 36.9200 },
      { name: 'Elbistan', lat: 38.2100, lon: 37.2000 },
      { name: 'Afşin', lat: 38.2500, lon: 36.9200 },
      { name: 'Göksun', lat: 38.0200, lon: 36.5000 },
      { name: 'Türkoğlu', lat: 37.3800, lon: 36.8400 },
      { name: 'Pazarcık', lat: 37.4900, lon: 37.2900 },
      { name: 'Andırın', lat: 37.5800, lon: 36.3500 },
      { name: 'Çağlayancerit', lat: 37.7500, lon: 37.3000 },
      { name: 'Nurhak', lat: 37.9600, lon: 37.4400 },
      { name: 'Ekinözü', lat: 38.0600, lon: 37.1900 },
    ],
  },
];

export function getWeatherCityBySlug(slug: string): WeatherCity | undefined {
  return WEATHER_CITIES.find(c => c.slug === slug);
}

// WMO hava durumu kodları → Türkçe açıklama ve ikon
export function getWeatherDescription(code: number): { text: string; icon: string } {
  const map: Record<number, { text: string; icon: string }> = {
    0: { text: 'Açık', icon: 'clear_day' },
    1: { text: 'Çoğunlukla Açık', icon: 'partly_cloudy_day' },
    2: { text: 'Parçalı Bulutlu', icon: 'partly_cloudy_day' },
    3: { text: 'Kapalı', icon: 'cloud' },
    45: { text: 'Sisli', icon: 'foggy' },
    48: { text: 'Kırağılı Sis', icon: 'foggy' },
    51: { text: 'Hafif Çisenti', icon: 'rainy' },
    53: { text: 'Orta Çisenti', icon: 'rainy' },
    55: { text: 'Yoğun Çisenti', icon: 'rainy' },
    56: { text: 'Dondurucu Çisenti', icon: 'weather_snowy' },
    57: { text: 'Yoğun Dondurucu Çisenti', icon: 'weather_snowy' },
    61: { text: 'Hafif Yağmur', icon: 'rainy' },
    63: { text: 'Orta Yağmur', icon: 'rainy' },
    65: { text: 'Şiddetli Yağmur', icon: 'rainy' },
    66: { text: 'Dondurucu Yağmur', icon: 'weather_snowy' },
    67: { text: 'Yoğun Dondurucu Yağmur', icon: 'weather_snowy' },
    71: { text: 'Hafif Kar', icon: 'weather_snowy' },
    73: { text: 'Orta Kar', icon: 'weather_snowy' },
    75: { text: 'Yoğun Kar', icon: 'weather_snowy' },
    77: { text: 'Kar Taneleri', icon: 'weather_snowy' },
    80: { text: 'Hafif Sağanak', icon: 'rainy' },
    81: { text: 'Orta Sağanak', icon: 'rainy' },
    82: { text: 'Şiddetli Sağanak', icon: 'rainy' },
    85: { text: 'Hafif Kar Sağanağı', icon: 'weather_snowy' },
    86: { text: 'Yoğun Kar Sağanağı', icon: 'weather_snowy' },
    95: { text: 'Gök Gürültülü Fırtına', icon: 'thunderstorm' },
    96: { text: 'Dolu ile Fırtına', icon: 'thunderstorm' },
    99: { text: 'Şiddetli Dolu Fırtınası', icon: 'thunderstorm' },
  };
  return map[code] || { text: 'Bilinmiyor', icon: 'cloud' };
}
