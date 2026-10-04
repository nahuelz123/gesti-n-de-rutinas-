<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(Request $request): View
    {
        return view('legal.privacy', $this->pageContext($request));
    }

    public function terms(Request $request): View
    {
        return view('legal.terms', $this->pageContext($request));
    }

    public function cookies(Request $request): View
    {
        return view('legal.cookies', $this->pageContext($request));
    }

    private function pageContext(Request $request): array
    {
        $gym = $request->user()?->gym;
        $inviteCode = trim((string) $request->query('gym'));

        if (! $gym && $inviteCode !== '') {
            $gym = Gym::query()
                ->where('invite_code', $inviteCode)
                ->where('active', true)
                ->first();
        }

        return [
            'gym' => $gym,
            'operatorName' => config('legal.operator_name') ?: config('app.name', 'VisionFit'),
            'contactEmail' => config('legal.contact_email'),
            'aiProviderHost' => parse_url((string) config('services.deepseek.base_url'), PHP_URL_HOST) ?: 'el proveedor configurado',
            'photoProviderHost' => 'Google Gemini',
        ];
    }
}
