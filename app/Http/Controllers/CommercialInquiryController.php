<?php

namespace App\Http\Controllers;

use App\Mail\CommercialReceipt;
use App\Models\CommercialInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommercialInquiryController extends Controller
{
    public function form(string $type)
    {
        $titles = ['baja' => 'Baja de servicio', 'arrepentimiento' => 'Botón de arrepentimiento', 'privacidad/solicitud' => 'Consultas sobre tus datos'];
        abort_unless(isset($titles[$type]), 404);
        $title = $titles[$type];

        return view('marketing.consumer-request', compact('type', 'title') + ['meta' => ['title' => $title.' | VisionFit', 'description' => 'Registrá tu solicitud sin crear una cuenta adicional.', 'path' => '/'.$type]]);
    }

    public function store(Request $request, string $type = 'contact')
    {
        abort_unless(in_array($type, ['contact', 'baja', 'arrepentimiento', 'privacidad/solicitud']), 404);
        if ($request->filled('website')) {
            return redirect('/contacto/recibido')->with('commercial_reference', (string) Str::uuid());
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'audience' => ['nullable', Rule::in(['personal', 'coach', 'gym', 'other'])],
            'plan' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'privacy_accepted' => $type === 'contact' ? ['accepted'] : ['nullable'],
        ], ['privacy_accepted.accepted' => 'Leé y aceptá el uso de datos para gestionar tu solicitud.']);
        unset($data['privacy_accepted']);
        $inquiry = CommercialInquiry::create($data + [
            'reference' => (string) Str::uuid(), 'type' => $type, 'status' => 'new',
            'privacy_version' => config('legal.versions.privacy'), 'consented_at' => now(),
        ]);

        if (! in_array(config('mail.default'), ['log', 'array'])) {
            try {
                Mail::to($inquiry->email)->send(new CommercialReceipt($inquiry));
                if (filter_var(config('commerce.inbox_email'), FILTER_VALIDATE_EMAIL)) {
                    Mail::to(config('commerce.inbox_email'))->send(new CommercialReceipt($inquiry, true));
                }
            } catch (\Throwable $exception) {
                Log::warning('Commercial inquiry email delivery failed', ['reference' => $inquiry->reference, 'exception' => $exception::class]);
            }
        }

        return redirect('/contacto/recibido')->with('commercial_reference', $inquiry->reference);
    }

    public function received(Request $request)
    {
        return view('marketing.received', ['reference' => $request->session()->get('commercial_reference'), 'meta' => ['title' => 'Solicitud recibida | VisionFit', 'description' => 'Confirmación de recepción de tu solicitud.', 'path' => '/contacto/recibido']]);
    }
}
