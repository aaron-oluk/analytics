# Analytics

Self-hosted, cookie-free website analytics. A registered user adds a site, pastes a small script onto that site, and this app records pageviews, sessions, and referrers, then shows them on a dashboard.

It is closer to Plausible or Fathom than to Google Analytics: no cookies, no third-party pixels, and visitors are not tracked across days.

## How it works

```
Visitor loads a tracked page
        │
        ▼
public/tracker.js  ──POST /api/collect──►  CollectController
        │                                      │
        │                                      ▼  (deferred, after 204)
        │                                   RecordHit
        │                                      │
        │                                      ▼
        │                                   events table
        │
        └──page hide / SPA route change──►  POST /api/collect/duration
                                               │
                                               ▼
                                          UpdateHitDuration
                                               │
                                               ▼
                                    duration_seconds on the event
```

Every night, `analytics:aggregate` rolls yesterday’s raw events into `daily_stats` and `stat_breakdowns`. The dashboard reads those rollups for history and queries `events` only for today (and for the “right now” visitor count).

### 1. You add a site

Register (Laravel Breeze), then create a site with a name, domain (`example.com`, no `https://`), and timezone. The domain is unique and stored without a `www.` prefix. Each site belongs to one user; `SitePolicy` keeps other users off your dashboards.

After saving, the site page shows a snippet:

```html
<script defer src="https://your-analytics-host/tracker.js" data-site="example.com"></script>
```

Paste that before `</body>` on every page you want to track.

### 2. The tracker records a pageview

`public/tracker.js` is a small IIFE with no cookies and no fingerprinting library.

- Honors `navigator.doNotTrack === '1'` and bails out.
- POSTs `{ domain, pathname, referrer, utm_* }` to `/api/collect`.
- On tab hide, page unload, or an in-app route change, sends time-on-page to `/api/collect/duration` via `sendBeacon`.
- Treats `pushState` / `replaceState` / `popstate` as new pageviews so SPAs work.

The script origin is derived from its own `src`, so the same file works in local and production as long as it is served from this app.

### 3. Ingestion stays thin

`POST /api/collect` and `POST /api/collect/duration` are public (the snippet cannot hide a secret). They are gated by:

- A domain lookup: unknown domains get `204` and are ignored.
- A per-IP rate limit of 300 requests/minute (`analytics-ingest`).
- Open CORS on `api/*` so browsers on customer sites can POST here.

`CollectController` looks the site up from cache (10 minutes), strips same-site referrers, and dispatches a job. It does not parse user-agents or write events on the request.

### 4. A queued job does the real work

`RecordHit` (queue name `analytics`):

1. Drops crawlers (`CrawlerDetect`).
2. Builds an anonymous visitor hash from IP + user-agent + a **daily-rotating salt** (`VisitorIdentity`). Same person, next day → different hash, so days cannot be joined.
3. Continues or starts a session (default 30 minutes of inactivity).
4. Parses device / browser / OS (`jenssegers/agent`).
5. Optionally resolves country via `GeoLocator` (default driver is `null`, so country is empty until you wire MaxMind).
6. Inserts one `events` row.

`UpdateHitDuration` recomputes the same visitor hash and writes `duration_seconds` onto the latest matching pageview. The browser never holds a server-issued id.

The default queue is Laravel’s `deferred` driver: `/api/collect` returns `204`, then the job runs in the same request after the response is sent. No `queue:work` process is required after deploy. Switch `QUEUE_CONNECTION` to `database` (and run a worker on `--queue=analytics`) only if you want ingest fully off the PHP-FPM process.

### 5. The dashboard merges rollups with today

`/dashboard` redirects to `/sites`. Opening a site shows:

- Visitors right now (distinct hashes in the last 5 minutes)
- Visitors, pageviews, bounce rate, average duration
- A daily visitors chart
- Top pages, referrers, countries, devices, browsers
- Range chips: today, 7 / 30 / 90 days

`StatsRepository` is the only read path. Days before today come from `daily_stats` / `stat_breakdowns`. Today is still accumulating, so it is counted live from `events` and merged in.

A bounce is a session with exactly one pageview.

## Privacy model

No cookies, no localStorage, no consent banner by design.

| Stored | Not stored |
| --- | --- |
| Daily-rotating MD5 of salt + IP + UA | Raw IP |
| Pathname, external referrer host, UTM | Full referrer URL after ingest |
| Device / browser / OS | Cross-day visitor identity |
| Optional ISO country code | Names, emails, user ids of visitors |

Session and “seen today” flags live in cache with a short TTL, not as PII in the database.

Raw `events` older than `analytics.raw_event_retention_days` (90) are pruned at 01:00 after they have already been aggregated.

## Data model

| Table | Role |
| --- | --- |
| `users` | Dashboard accounts (Breeze) |
| `sites` | Tracked properties (`domain`, `timezone`, short `public_id`) |
| `events` | One row per pageview (hot path + “today” + realtime) |
| `daily_stats` | Per-site, per-day totals |
| `stat_breakdowns` | Per-site, per-day, per-dimension rows (`page`, `referrer`, `country`, `device`, `browser`, `os`, `utm_*`) |

## Scheduled work

Defined in `routes/console.php`. Run a scheduler (`php artisan schedule:work` locally, or cron `* * * * * php artisan schedule:run`).

| When | What |
| --- | --- |
| 00:10 | `analytics:aggregate` — roll up yesterday into `daily_stats` / `stat_breakdowns` |
| 01:00 | Delete raw events older than the retention window |

You can backfill a day with `php artisan analytics:aggregate 2026-08-22`.

## Configuration

See `config/analytics.php` and `.env`:

| Key | Default | Meaning |
| --- | --- | --- |
| `QUEUE_CONNECTION` | `deferred` | Runs ingest after the HTTP response; no worker |
| `ANALYTICS_QUEUE` | `analytics` | Queue name only if you switch to `database` / `redis` |
| `ANALYTICS_GEOIP_DRIVER` | `null` | `null` skips country; swap in a MaxMind driver later |
| `session_timeout_minutes` | `30` | Inactivity before a new session |
| `raw_event_retention_days` | `90` | How long raw hits are kept after rollup |

Tests force `sync` so jobs run inline.

## Local setup

```bash
composer setup          # install, .env, key, migrate, npm build
composer run dev        # serve + logs + vite
```

Or the usual Laravel steps: copy `.env.example`, `php artisan key:generate`, `php artisan migrate`, `npm install && npm run dev`.

Default DB is SQLite. Seed a test user with `php artisan db:seed` (`test@example.com`).

```bash
composer test           # phpunit
```

## Layout of the interesting code

```
app/Http/Controllers/Api/CollectController.php   accept + dispatch
app/Jobs/RecordHit.php                           enrich + write event
app/Jobs/UpdateHitDuration.php                   time-on-page
app/Services/Analytics/VisitorIdentity.php       hash + session
app/Services/Analytics/StatsRepository.php       dashboard reads
app/Console/Commands/AggregateDailyStats.php     nightly rollup
public/tracker.js                                customer-site snippet
```

Auth, profile, and Blade layout come from Laravel Breeze (Blade + Alpine + Tailwind).

## Why "Meridian"

This app's original architecture design document — the one that laid out the ingestion pipeline, two-tier storage, privacy model, and scaling path described above — was titled **Meridian**. It was the working codename for this design, not a dependency or component: the name doesn't appear anywhere in the code, composer dependencies, or tracker.

Confusingly, [Google Meridian](https://developers.google.com/meridian/docs/basics/meridian-introduction) is a different, unrelated open-source project: a **marketing mix model (MMM)** that estimates how much each ad channel contributed to a KPI using aggregated spend and outcome data. It's Bayesian causal inference on weekly/geo totals, not a pageview tracker, and shares nothing with this app beyond the name.

This app answers "who visited which pages." Google Meridian answers "which marketing channels caused the outcome." They could sit in the same stack later (export daily totals from here into an MMM), but they solve different problems and neither one is built on the other.
