<?php

namespace App\Services;

use App\Mail\MembershipReminderDigest;
use App\Models\CommercialReminderRun;
use App\Models\CommercialSettings;
use App\Models\User;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Mail\Transport\SesV2Transport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Throwable;

class CommercialReminderDispatcher
{
    public function __construct(private CommercialMembershipReminders $reminders) {}

    public function recipient(): ?User
    {
        return User::whereKey((CommercialSettings::find(1)?->values ?? [])['reminder_recipient_id'] ?? 0)->where('role', 'super_admin')->where('commercial_access_enabled', true)->first();
    }

    public function ready(): bool
    {
        try {
            // Resolve the framework's effective transport, including MAIL_URL overrides.
            // Constructing it does not submit or connect a message.
            $transport = (new MailManager(app()))->mailer()->getSymfonyTransport();
            $deliverable = $transport instanceof SmtpTransport || $transport instanceof SendmailTransport
                || $transport instanceof SesTransport || $transport instanceof SesV2Transport
                || $transport instanceof ResendTransport || $transport instanceof PostmarkApiTransport;

            return $deliverable && parse_url(config('commerce.public_url'), PHP_URL_SCHEME) === 'https'
                && filter_var($this->recipient()?->email, FILTER_VALIDATE_EMAIL);
        } catch (Throwable) {
            return false;
        }
    }

    public function dispatch(): string
    {
        if (! ((CommercialSettings::find(1)?->values ?? [])['reminders_enabled'] ?? false)) {
            return 'disabled';
        }
        if (now()->hour < 9 || now()->hour >= 21) {
            return 'outside_hours';
        }
        if (! $this->ready()) {
            return 'not_ready';
        }
        $recipient = $this->recipient();
        if (! $recipient) {
            return 'not_ready';
        }
        $day = now()->toDateString();
        // Claim before SMTP: a crash after handing over an email must not resend it.
        $claimed = DB::table('commercial_reminder_runs')->insertOrIgnore(['day' => $day, 'recipient_user_id' => $recipient->id, 'status' => 'sending', 'item_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
        if (! $claimed) {
            return 'already_attempted';
        }
        $run = CommercialReminderRun::where('day', $day)->where('recipient_user_id', $recipient->id)->firstOrFail();
        try {
            $total = $this->reminders->query()->count();
            $items = $this->reminders->query()->limit(100)->get();
            if ($items->isEmpty()) {
                $run->update(['status' => 'empty']);

                return 'empty';
            }
            $run->update(['item_count' => $items->count()]);

            return DB::transaction(function () use ($recipient, $run, $items, $total) {
                // Same settings → user order as configuration writes. Revocation that
                // commits first is observed; later changes wait until mail handoff.
                $settings = CommercialSettings::lockForUpdate()->findOrFail(1)->values;
                $current = User::whereKey($recipient->id)->lockForUpdate()
                    ->where('role', 'super_admin')->where('commercial_access_enabled', true)->first();
                if (! ($settings['reminders_enabled'] ?? false)
                    || (int) ($settings['reminder_recipient_id'] ?? 0) !== $recipient->id
                    || ! $current || ! filter_var($current->email, FILTER_VALIDATE_EMAIL)) {
                    $run->update(['status' => 'revoked']);

                    return 'not_ready';
                }
                Mail::to($current->email)->send(new MembershipReminderDigest($items, $total, now()));
                $run->update(['status' => 'sent', 'sent_at' => now()]);

                return 'sent';
            });
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'error_code' => 'delivery_failed']);
            Log::warning('Membership reminder was not confirmed by the transport.', ['run_id' => $run->id, 'exception' => $exception::class]);

            return 'failed';
        }
    }
}
