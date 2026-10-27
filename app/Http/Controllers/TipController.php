<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\TipSubmission;
use App\Services\PublicImageService;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function create()
    {
        $cities = City::active()->orderBy('sort_order')->get();
        return view('tips.create', compact('cities'));
    }

    public function store(Request $request, PublicImageService $images)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'city_id' => 'nullable|exists:cities,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'image.max' => 'Görsel en fazla 5 MB olabilir.',
            'image.mimes' => 'Görsel JPG, PNG veya WebP formatında olmalıdır.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $images->saveAsWebp($request->file('image'), 'tips');
        }
        unset($validated['image']);

        $validated['ip_address'] = $request->ip();
        $validated['user_agent'] = $request->userAgent();
        $validated['status'] = 'pending';

        TipSubmission::create($validated);

        return redirect()->route('tip.create')
            ->with('success', 'Haber ihbarınız başarıyla gönderildi! Editör ekibimiz en kısa sürede inceleyecektir.');
    }
}
