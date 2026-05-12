<?php
declare(strict_types=1);

final class Config
{
    public static function storageDir(): string
    {
        $configured = trim((string) envget('STORAGE_DIR', ''));
        if ($configured !== '') {
            return rtrim($configured, "/\\");
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    }

    public static function storagePath(string $path = ''): string
    {
        $path = trim($path, "/\\");
        if ($path === '') {
            return self::storageDir();
        }

        return self::storageDir() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    public static function maxJsonBodyBytes(): int
    {
        return max(1024, (int) envget('MAX_JSON_BODY_BYTES', '65536'));
    }

    public static function sensitiveEnv(string $key, string $default = ''): string
    {
        $value = trim((string) envget($key, $default));

        return self::isPlaceholderSecret($value) ? '' : $value;
    }

    private static function isPlaceholderSecret(string $value): bool
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['change-me', 'changeme', 'placeholder', 'replace-me', 'your-secret', 'your-secret-here'], true)
            || str_contains($normalized, 'change-me')
            || str_contains($normalized, 'changeme')
            || str_contains($normalized, 'placeholder')
            || str_contains($normalized, 'replace-me')
            || str_contains($normalized, 'generate-');
    }

    public static function origin(): string
    {
        $origin = trim((string) envget('APP_ORIGIN', ''));
        if ($origin === '') {
            $appUrl = trim((string) envget('APP_URL', ''));
            if ($appUrl !== '') {
                $parts = parse_url($appUrl);
                $scheme = (string)($parts['scheme'] ?? 'https');
                $host = (string)($parts['host'] ?? '');
                $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
                if ($host !== '') {
                    $origin = $scheme . '://' . $host . $port;
                }
            }
        }

        if ($origin === '') {
            $origin = 'https://novaweb.ro';
        }

        return rtrim($origin, '/');
    }

    public static function basePath(): string
    {
        $base = trim((string) envget('APP_BASE', '/tools/audit-seo-gratuit'));
        if ($base === '') {
            $base = '/tools/audit-seo-gratuit';
        }

        $base = '/' . ltrim($base, '/');
        return rtrim($base, '/');
    }

    public static function baseUrl(): string
    {
        return self::origin() . self::basePath();
    }

    public static function canonicalUrl(string $path = ''): string
    {
        $path = trim($path);
        if ($path === '') {
            return self::baseUrl() . '/';
        }

        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    public static function assetUrl(string $path): string
    {
        $normalizedPath = ltrim($path, '/');
        $url = self::basePath() . '/' . $normalizedPath;
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);

        if (is_file($file)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
        }

        return $url;
    }

    public static function toolTitle(): string
    {
        return 'Audit SEO gratuit pentru site-ul tău: raport în 60 secunde | NovaWeb';
    }

    public static function toolDescription(): string
    {
        return 'Introdu URL-ul, primești în 60 de secunde un audit SEO complet: 50+ verificări on-page, problemele prioritare și pașii de rezolvare. Fără cont, fără card. Raport pe email opțional.';
    }

    public static function serviceCatalog(): array
    {
        return [
            'seo_optimization' => [
                'name' => 'Optimizare SEO',
                'url' => 'https://novaweb.ro/optimizare-seo/',
                'cta_primary' => 'Vreau recomandari personalizate',
                'cta_secondary' => 'Solicita oferta SEO',
            ],
            'website_redesign' => [
                'name' => 'Refacere site',
                'url' => null,
                'cta_primary' => 'Solicita oferta personalizata',
                'cta_secondary' => 'Vorbeste cu un consultant',
            ],
            'content' => [
                'name' => 'Continut SEO',
                'url' => null,
                'cta_primary' => 'Vreau recomandari editoriale',
                'cta_secondary' => 'Cere recomandari',
            ],
            'local_seo' => [
                'name' => 'SEO local',
                'url' => null,
                'cta_primary' => 'Vreau recomandari locale',
                'cta_secondary' => 'Solicita audit manual',
            ],
            'technical' => [
                'name' => 'Refactor tehnic',
                'url' => null,
                'cta_primary' => 'Ajutati-ma cu implementarea',
                'cta_secondary' => 'Solicita audit manual',
            ],
            'premium_audit' => [
                'name' => 'Audit avansat',
                'url' => null,
                'cta_primary' => 'Solicita audit avansat',
                'cta_secondary' => 'Vorbeste cu un consultant',
            ],
        ];
    }

    public static function consultant(): array
    {
        return [
            'name' => 'Consultant Senior NovaWeb',
            'role' => 'SEO & web strategy',
            'summary' => 'Analizam personal fiecare cerere serioasa si revenim in maximum 24h.',
        ];
    }

    public static function testimonials(): array
    {
        return [
            [
                'quote' => '+340% trafic organic in 6 luni dupa restructurarea continutului si a paginilor de servicii.',
                'author' => 'Client B2B, servicii profesionale',
            ],
            [
                'quote' => 'Raportul a aratat imediat unde pierdeam clickuri si ce trebuia reparat prima data.',
                'author' => 'Magazin online local',
            ],
        ];
    }

    public static function logos(): array
    {
        return ['B2B', 'Local Commerce', 'Healthcare', 'Education', 'Real Estate'];
    }

    public static function caseStudies(): array
    {
        return [
            [
                'title' => 'Structura si intentia au dublat traficul organic.',
                'summary' => 'Am mutat focusul pe pagini comerciale, am rescris title/meta si am clarificat ierarhia.',
                'metric' => '+102% trafic organic',
                'url' => null,
            ],
            [
                'title' => 'SEO local clarificat pentru o firma cu mai multe zone deservite.',
                'summary' => 'Am introdus NAP, LocalBusiness si pagini dedicate pentru orasele prioritare.',
                'metric' => '+46% apeluri din cautari locale',
                'url' => null,
            ],
            [
                'title' => 'Refacerea continutului a crescut conversiile din trafic existent.',
                'summary' => 'Am prioritizat paginile cu intentie ridicata si am rezolvat blocajele tehnice majore.',
                'metric' => '+28% lead-uri din organic',
                'url' => null,
            ],
        ];
    }

    public static function faqItems(): array
    {
        return [
            [
                'question' => 'Ce verifică auditul SEO?',
                'answer' => 'Auditul verifică elementele on-page și semnalele detectabile în HTML: title, meta description, H1/H2, canonical, robots, imagini, date structurate și indicatori locali (pentru modul „Pagină locală").',
            ],
            [
                'question' => 'Scorul include viteza site-ului?',
                'answer' => 'Nu. Scorul acoperă SEO on-page și indexare. Pentru Core Web Vitals și viteză recomandăm PageSpeed Insights - sunt două analize complementare.',
            ],
            [
                'question' => 'Pot folosi auditul și pentru pagini locale?',
                'answer' => 'Da. Alege „Pagină locală" înainte de a introduce URL-ul. Adaugă verificări pentru semnale locale on-page: schema LocalBusiness, orașul în titluri, telefon/adresă vizibile, link sau hartă etc.',
            ],
            [
                'question' => 'De ce e util să las emailul?',
                'answer' => 'Primești raportul complet în PDF, formatat pentru a-l păstra sau a-l trimite la developer. Dacă nu vrei, poți vedea rezultatele direct pe pagină fără să lași nimic. Emailul nu ajunge pe liste de marketing dacă nu bifezi expres newsletter-ul.',
            ],
            [
                'question' => 'Ce se întâmplă dacă site-ul meu nu poate fi analizat automat?',
                'answer' => 'În cazuri rare (pagini care blochează crawlerele, erori de server, conținut încărcat exclusiv prin JavaScript greu), auditul poate eșua. Scrie-ne linkul și îl verificăm manual - fără cost.',
            ],
            [
                'question' => 'Cum primesc raportul SEO?',
                'answer' => 'Vezi raportul preview imediat pe pagină: scor, problemele de top și categoriile afectate. Pentru raportul complet (cu toate verificările, recomandările pas cu pas și PDF descărcabil), introdu emailul. Trimitem raportul instant și păstrezi acces la el oricând prin link.',
            ],
            [
                'question' => 'În cât timp e gata auditul SEO?',
                'answer' => 'Auditul rulează în 30-60 de secunde pentru paginile standard. Pentru pagini cu mult JavaScript sau resurse externe, poate dura până la 90 de secunde. Rezultatul apare direct pe pagină, fără reîncărcare.',
            ],
            [
                'question' => 'Auditul e cu adevărat gratuit sau există costuri ascunse?',
                'answer' => 'Da, e complet gratuit. Nu cerem card, nu cerem cont, nu există versiune „premium". Singurul moment când lași date este dacă vrei raportul complet pe email (opțional). Dacă ai nevoie de implementare sau revizuire manuală a raportului, oferim servicii plătite separat - dar auditul în sine rămâne gratis.',
            ],
            [
                'question' => 'Pot vedea exemplu de raport SEO înainte să-mi analizez site-ul?',
                'answer' => 'Cea mai rapidă cale este să introduci URL-ul site-ului tău. Auditul durează 60 de secunde și arată exact cum arată raportul. Dacă vrei să testezi mai întâi, încearcă cu URL-ul unui concurent sau al unui site public - tool-ul nu necesită deținerea site-ului analizat.',
            ],
        ];
    }

    public static function analysisCount(): int
    {
        $cacheDir = self::storagePath('cache');
        if (!is_dir($cacheDir)) {
            return 0;
        }

        $count = 0;
        $files = scandir($cacheDir);
        if (!is_array($files)) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $count++;
        }

        return $count;
    }

    public static function microTrust(): array
    {
        return [
            sprintf('%d check-uri tehnice analizate', count(Advice::definitions())),
            'Audit gratuit, fără înregistrare obligatorie',
            'Datele tale rămân ale tale',
            'GDPR aligned',
        ];
    }

    public static function leadNotifyEmail(): ?string
    {
        $email = trim((string) envget('LEAD_NOTIFY_EMAIL', 'contact@novaweb.ro'));
        return $email !== '' ? $email : null;
    }

    public static function recaptchaSiteKey(): string
    {
        return trim((string) envget('RECAPTCHA_SITE_KEY', ''));
    }

    public static function recaptchaSecretKey(): string
    {
        return self::sensitiveEnv('RECAPTCHA_SECRET_KEY');
    }

    public static function recaptchaMinScore(): float
    {
        $score = (float) envget('RECAPTCHA_MIN_SCORE', '0.5');
        if ($score < 0.0 || $score > 1.0) {
            return 0.5;
        }

        return $score;
    }
}
