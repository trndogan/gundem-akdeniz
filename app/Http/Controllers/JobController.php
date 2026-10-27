<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::active()->with('city')->latest();

        if ($request->filled('city')) {
            $city = City::where('slug', $request->city)->first();
            if ($city) {
                $query->byCity($city->id);
            }
        }

        if ($request->filled('sector')) {
            $query->bySector($request->sector);
        }

        if ($request->filled('type')) {
            $query->where('employment_type', $request->type);
        }

        $jobs = $query->paginate(20);

        $cities = City::active()->orderBy('sort_order')->get();
        $sectors = JobListing::active()->whereNotNull('sector')->distinct()->pluck('sector')->sort();

        return view('jobs.index', compact('jobs', 'cities', 'sectors'));
    }

    public function create()
    {
        $cities = City::active()->orderBy('sort_order')->get();
        return view('jobs.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'district' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full-time,part-time,contract,internship,freelance,temporary',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'required|string|min:30',
            'deadline' => 'nullable|date|after:today',
        ], [
            'title.required' => 'Pozisyon adı zorunludur.',
            'company_name.required' => 'Şirket adı zorunludur.',
            'description.required' => 'İş tanımı zorunludur.',
            'description.min' => 'İş tanımı en az 30 karakter olmalıdır.',
            'deadline.after' => 'Son başvuru tarihi bugünden sonra olmalıdır.',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['is_active'] = false; // Admin onayı bekleyecek
        $validated['description'] = strip_tags($validated['description'], '<p><br><ul><ol><li><strong><b><em><i>');

        JobListing::create($validated);

        return redirect()->route('job.index')->with('success', 'İlanınız başarıyla gönderildi! Yönetici onayından sonra yayınlanacaktır.');
    }

    public function show(string $slug)
    {
        $job = JobListing::where('slug', $slug)->firstOrFail();

        $sessionKey = 'viewed_job_' . $job->id;
        if (!session()->has($sessionKey)) {
            $job->incrementViewCount();
            session()->put($sessionKey, true);
        }

        $relatedJobs = JobListing::active()
            ->where('id', '!=', $job->id)
            ->where(function ($q) use ($job) {
                $q->where('city_id', $job->city_id)
                  ->orWhere('sector', $job->sector);
            })
            ->latest()
            ->take(4)
            ->get();

        return view('jobs.show', compact('job', 'relatedJobs'));
    }
}
