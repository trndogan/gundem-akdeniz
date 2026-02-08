import { NextRequest, NextResponse } from 'next/server';
import { getWeatherCityBySlug } from '@/lib/weather-cities';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ city: string }> }
) {
  const { city } = await params;
  const cityData = getWeatherCityBySlug(city);

  if (!cityData) {
    return NextResponse.json({ error: 'Geçersiz şehir' }, { status: 400 });
  }

  try {
    // İl merkezi + tüm ilçeler için koordinatları topla
    const locations = [
      { name: cityData.label + ' (Merkez)', lat: cityData.lat, lon: cityData.lon },
      ...cityData.districts.map(d => ({ name: d.name, lat: d.lat, lon: d.lon })),
    ];

    // Open-Meteo API - tüm ilçeler için tek seferde batch çekemiyoruz, il merkezi + ilçeleri paralel çekeceğiz
    const allLats = locations.map(l => l.lat).join(',');
    const allLons = locations.map(l => l.lon).join(',');

    // İl merkezi için 7 günlük tahmin
    const forecastUrl = `https://api.open-meteo.com/v1/forecast?latitude=${cityData.lat}&longitude=${cityData.lon}&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,apparent_temperature&timezone=Europe/Istanbul&forecast_days=7`;

    const forecastRes = await fetch(forecastUrl, { next: { revalidate: 1800 } });
    if (!forecastRes.ok) throw new Error('Forecast API error');
    const forecastData = await forecastRes.json();

    // İlçeler için anlık sıcaklık (batch request)
    const districtsUrl = `https://api.open-meteo.com/v1/forecast?latitude=${allLats}&longitude=${allLons}&current=temperature_2m,weather_code,wind_speed_10m&timezone=Europe/Istanbul`;

    const districtsRes = await fetch(districtsUrl, { next: { revalidate: 1800 } });
    if (!districtsRes.ok) throw new Error('Districts API error');
    const districtsRaw = await districtsRes.json();

    // Open-Meteo batch response: tek lokasyon ise obje, çoklu ise dizi döner
    const districtsData = Array.isArray(districtsRaw) ? districtsRaw : [districtsRaw];

    const districts = locations.map((loc, i) => ({
      name: loc.name,
      temperature: districtsData[i]?.current?.temperature_2m ?? null,
      weatherCode: districtsData[i]?.current?.weather_code ?? 0,
      windSpeed: districtsData[i]?.current?.wind_speed_10m ?? null,
    }));

    // 7 günlük tahmin
    const daily = forecastData.daily;
    const forecast = daily.time.map((date: string, i: number) => ({
      date,
      weatherCode: daily.weather_code[i],
      tempMax: daily.temperature_2m_max[i],
      tempMin: daily.temperature_2m_min[i],
      precipitation: daily.precipitation_sum[i],
      windSpeed: daily.wind_speed_10m_max[i],
    }));

    // Anlık durum
    const current = {
      temperature: forecastData.current.temperature_2m,
      feelsLike: forecastData.current.apparent_temperature,
      humidity: forecastData.current.relative_humidity_2m,
      weatherCode: forecastData.current.weather_code,
      windSpeed: forecastData.current.wind_speed_10m,
    };

    return NextResponse.json({
      city: cityData.label,
      slug: cityData.slug,
      current,
      forecast,
      districts,
    });
  } catch (error) {
    console.error('Weather API error:', error);
    return NextResponse.json({ error: 'Hava durumu verileri alınamadı' }, { status: 500 });
  }
}
