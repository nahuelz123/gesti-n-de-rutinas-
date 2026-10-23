# Rest notifications

Rest deadlines are stored in the shared database. A separate always-on worker sends Web Push, so delivery does not depend on the workout tab continuing to execute JavaScript.

## Production

- Deploy the same revision to the web service and a worker service.
- Run migrations during deployment: `php artisan migrate --force`.
- Worker start command: `php artisan rest-alerts:work --sleep=1`.
- Share the same database configuration and **the same persistent APP_KEY** between both services. Changing APP_KEY prevents decryption of push subscriptions and VAPID keys.
- Set `APP_URL` to the public HTTPS app URL in both services. The VAPID identity defaults to this URL; optionally set `WEB_PUSH_SUBJECT` to a public HTTPS URL or `mailto:` contact.
- VAPID keys are generated once and stored encrypted in the shared database. Do not generate new keys on deployment or per process.
- Keep one worker running without sleeping/scaling to zero. Database row locks make multiple workers safe, but one suffices initially.
- The worker retries transient delivery errors with a short delay; it discards alerts more than 90 seconds late. Permanent expired push subscriptions are removed. Completed alerts are pruned after seven days; unused subscriptions after 90 days.

## iPhone setup

Use iOS/iPadOS 16.4 or newer. Open VisionFit in Safari, use **Share → Add to Home Screen**, open that installed app, sign in, then tap **ACTIVAR AVISOS** during a workout and allow notifications. A Safari tab alone does not support iPhone Web Push.

Logout removes only the subscription associated with the current signed-in session, cancelling its pending alerts even if browser JavaScript fails. The browser also attempts to unsubscribe the device. Other devices remain subscribed.

An active network connection is required when starting or cancelling rest. Confirm the “Aviso de descanso programado” status before locking the screen. OS settings, Focus modes, connectivity and the push provider can delay or suppress delivery. Sound and vibration are controlled by the phone; the app cannot guarantee either. Cancellation cannot recall a notification already handed to the operating system.

The service worker only handles push and notification clicks. It has no offline fetch handler and does not cache authenticated pages or health information. Notification text is generic and contains no workout or health details.

## Verification

Run the normal PHP feature suite and `node --test tests/js/*.mjs`. On a physical iPhone, verify install, permission, start-and-lock, restart, skip, browser navigation during rest, and permission denied. A server test cannot confirm physical lock-screen delivery or vibration.

## Membership summaries

The normal worker checks optional reminder settings every15 minutes during Argentine daytime and launches the mail command in an independent child bounded to60 seconds. SMTP stays off the timer loop. --once processes rest alerts only. Email defaults disabled; see commercial-setup.md.
