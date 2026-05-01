<?php
declare(strict_types=1);

final class Advice
{
    /**
     * @return array<string,array<string,string>>
     */
    public static function definitions(): array
    {
        static $definitions = null;
        if (is_array($definitions)) {
            return $definitions;
        }

        $definitions = [
            'word_count_800' => self::def('Conținut suficient', 'Minim 800 de cuvinte atunci când pagina este informativă.', 'Extinde conținutul cu exemple, întrebări frecvente și blocuri care răspund clar intenției.', 'Pagina nu are suficientă substanță ca să câștige încredere și să acopere complet căutarea utilizatorului.', 'high', 'content', 'medium'),
            'intro_mentions_topic' => self::def('Tema apare în introducere', 'Termenul principal trebuie să apară în primele 100 de cuvinte.', 'Reformulează primele paragrafe ca să explici imediat subiectul și beneficiul.', 'Google și utilizatorul înțeleg mai greu despre ce este pagina, iar relevanța percepută scade.', 'medium', 'content', 'easy'),
            'h1_single' => self::def('H1 unic', 'Trebuie să existe exact un singur H1 relevant.', 'Păstrează un singur titlu principal și mută restul în H2/H3.', 'Mesajul principal al paginii devine ambiguu, ceea ce slăbește atât claritatea pentru user, cât și pentru motorul de căutare.', 'high', 'website_redesign', 'easy'),
            'headings_hierarchy' => self::def('Structură H2/H3 coerentă', 'Cel puțin 3 subtitluri H2/H3, fără sărit niveluri.', 'Rearanjează structura astfel încât pagina să poată fi scanată rapid și logic.', 'Pagina pare greu de parcurs, iar secțiunile importante nu ies în evidență suficient pentru a ghida cititorul spre conversie.', 'high', 'content', 'medium'),
            'lists_tables' => self::def('Liste sau tabele', 'Include cel puțin o listă sau un tabel în conținutul dens.', 'Transformă pasajele grele în liste și comparații ușor de scanat.', 'Fără elemente scanabile, utilizatorii abandonează mai repede și rețin mai greu mesajul principal.', 'medium', 'content', 'easy'),
            'images_in_body' => self::def('Imagini în corp', 'Cel puțin o imagine relevantă în conținut.', 'Adaugă imagini reale care clarifică oferta sau ilustrează procesul.', 'Pagina pare mai săracă și transmite mai puțină încredere, mai ales pe servicii sau pagini comerciale.', 'medium', 'website_redesign', 'easy'),
            'img_alt_ratio_80' => self::def('ALT completat', 'Cel puțin 80% dintre imaginile relevante să aibă ALT descriptiv.', 'Completează ALT-urile cu descrieri naturale, nu cu liste de cuvinte cheie.', 'Pierzi contexte suplimentare pentru imagini și accesibilitate, iar pagina comunică mai puțin clar conținutul vizual.', 'medium', 'content', 'easy'),
            'lazyload_images' => self::def('Lazy-load imagini', 'Imaginile mari trebuie încărcate lazy.', 'Activează loading="lazy" sau echivalentul din temă/plugin.', 'Pagina consumă resurse inutil la încărcare și poate produce o experiență mai lentă pe mobil.', 'medium', 'technical', 'medium'),
            'date_published' => self::def('Data publicării', 'Data publicării trebuie expusă în pagină sau schemă.', 'Afișează data publicării într-un element time sau în JSON-LD.', 'Fără dată publică, conținutul pare mai puțin credibil și mai greu de evaluat ca prospețime.', 'low', 'content', 'easy'),
            'date_modified' => self::def('Data actualizării', 'Data ultimei actualizări trebuie expusă în pagină sau schemă.', 'Afișează când a fost revizuită ultima dată pagina.', 'Utilizatorii și Google au mai puține semnale că informația este menținută la zi.', 'low', 'content', 'easy'),
            'author_visible_or_schema' => self::def('Autor vizibil sau schemă', 'Autorul trebuie să fie afișat în pagină sau definit în schemă.', 'Adaugă byline vizibil sau proprietatea author în JSON-LD.', 'Autoritatea conținutului este mai greu de evaluat, mai ales pentru pagini informative sau articole.', 'medium', 'content', 'easy'),
            'indexable' => self::def('Pagina este indexabilă', 'Pagina nu trebuie să aibă noindex dacă vrei trafic organic.', 'Elimină directivele noindex/none de pe paginile care trebuie să rankeze.', 'Dacă pagina nu poate fi indexată, nu are cum să aducă trafic organic indiferent cât de bun este conținutul.', 'high', 'technical', 'hard'),
            'canonical_present' => self::def('Canonical prezent', 'Trebuie să existe rel="canonical".', 'Adaugă canonical către varianta preferată a URL-ului.', 'Google poate interpreta mai multe variante ale aceleiași pagini și poate dilua semnalele SEO.', 'high', 'technical', 'medium'),
            'canonical_valid' => self::def('Canonical corect', 'Canonical-ul trebuie să fie pe același domeniu și către URL-ul corect.', 'Corectează canonical-ul astfel încât să indice exact pagina sau varianta canonică reală.', 'Un canonical greșit poate împinge valoarea SEO către altă pagină sau poate scoate din competiție pagina corectă.', 'high', 'technical', 'hard'),
            'url_clean' => self::def('URL curat', 'Slug scurt, descriptiv și fără parametri inutili.', 'Scurtează slug-ul și elimină parametrii inutili sau caracterele neprietenoase.', 'URL-ul comunică mai greu subiectul și poate scădea claritatea listării în căutare.', 'medium', 'website_redesign', 'medium'),
            'internal_links_present' => self::def('Linkuri interne', 'Cel puțin un link intern relevant în conținut.', 'Leagă pagina de alte pagini de servicii sau resurse utile din site.', 'Fără linkuri interne, pagina este mai izolată și transferă mai slab context și autoritate în interiorul site-ului.', 'high', 'seo_optimization', 'easy'),
            'external_links_present' => self::def('Linkuri externe utile', 'Cel puțin un link extern de referință atunci când contextul o cere.', 'Trimite către surse de încredere acolo unde susține credibilitatea informației.', 'Lipsa surselor externe poate face conținutul să pară mai slab documentat sau mai puțin credibil.', 'low', 'content', 'easy'),
            'title_length_ok' => self::def('Title optimizat', 'Title între aproximativ 35 și 65 de caractere.', 'Rescrie title-ul cu intenția principală și un beneficiu clar.', 'Title-ul este principalul pitch din Google. Dacă este vag sau prost dimensionat, rata de click scade imediat.', 'high', 'seo_optimization', 'easy'),
            'meta_description_ok' => self::def('Meta description optimizată', 'Meta description între aproximativ 120 și 170 de caractere.', 'Scrie un rezumat scurt, convingător și diferit de title.', 'Când descrierea este slabă sau lipsește, rezultatul tău convinge mai puțin în Google față de competiție.', 'high', 'seo_optimization', 'easy'),
            'og_minimal' => self::def('Open Graph minim', 'og:title, og:description, og:image și og:url trebuie definite.', 'Completează pachetul OG pentru distribuție corectă în social și mesagerie.', 'Pagina se prezintă mai slab atunci când este distribuită, ceea ce reduce încrederea și clickurile din share-uri.', 'low', 'website_redesign', 'easy'),
            'schema_article_recommended' => self::def('Schema Article / BlogPosting', 'Pagina ar trebui să expună schema Article sau BlogPosting completă.', 'Adaugă JSON-LD cu headline, image, author, datePublished și dateModified.', 'Google primește mai puțin context despre conținut, iar șansa de interpretare corectă și semnale bogate scade.', 'medium', 'technical', 'medium'),
            'lang_ro' => self::def('Limbă setată corect', 'html lang trebuie să fie ro sau ro-RO.', 'Setează limba corectă la nivel de document.', 'Fără limbă setată corect, pagina trimite semnale slabe despre audiența și localizarea conținutului.', 'medium', 'technical', 'easy'),
            'og_locale_or_inLanguage_ro' => self::def('Localizare română în metadata', 'og:locale sau inLanguage trebuie să indice română.', 'Completează localizarea în Open Graph sau schemă.', 'Contextul lingvistic este mai puțin clar pentru platforme externe și pentru motoarele de căutare.', 'low', 'technical', 'easy'),
            'date_format_ro' => self::def('Dată în format românesc', 'Datele vizibile trebuie să folosească luna în română.', 'Afișează datele în forma naturală pentru audiența locală.', 'Detaliile locale transmit mai puțină încredere și pagina pare mai puțin adaptată pieței din România.', 'low', 'content', 'easy'),
            'hreflang_pairs' => self::def('Hreflang corect', 'Versiunile alternative trebuie marcate cu hreflang.', 'Definește hreflang doar dacă există variante lingvistice reale.', 'Fără hreflang, Google poate servi varianta greșită de pagină pentru unele căutări internaționale sau multilingve.', 'low', 'technical', 'medium'),
            'faq_schema_present' => self::def('Schema FAQPage', 'Dacă ai FAQ real în conținut, expune-l și în schemă.', 'Mapează întrebările reale din pagină în JSON-LD FAQPage.', 'Pagina pierde șansa de a clarifica rapid intențiile secundare și de a transmite mai mult context despre subiect.', 'medium', 'content', 'medium'),
            'html_valid' => self::def('HTML fără erori majore', 'Markup-ul nu trebuie să aibă erori structurale majore.', 'Corectează tag-urile închise greșit, atributele invalide și id-urile duplicate.', 'Un DOM degradat crește riscul ca roboții și browserul să interpreteze inconsistent conținutul important.', 'medium', 'technical', 'hard'),
            'meta_robots_ok' => self::def('Meta robots corect', 'Meta robots nu trebuie să blocheze inutil indexarea sau follow.', 'Verifică instrucțiunile robots la nivel de pagină.', 'Setări greșite aici pot bloca indexarea sau transmiterea semnalelor interne chiar dacă restul paginii este bun.', 'high', 'technical', 'hard'),
            'image_dimensions_defined' => self::def('Dimensiuni pentru imagini', 'Imaginile relevante trebuie să aibă width și height definite.', 'Definește dimensiunile sau aspect-ratio pentru imaginile importante.', 'Pagina poate sări în timpul încărcării, iar experiența pe mobil devine mai slabă exact în zonele care ar trebui să inspire încredere.', 'medium', 'technical', 'medium'),
            'fonts_preload' => self::def('Fonturi preload', 'Fonturile critice pot fi preload-ate pentru a reduce flicker-ul.', 'Preîncarcă doar fonturile folosite deasupra fold-ului.', 'Textul își schimbă aspectul târziu, ceea ce dă senzația de încărcare instabilă și poate afecta percepția de calitate.', 'low', 'technical', 'medium'),
            'cls_risky_elements' => self::def('Risc CLS controlat', 'Elementele cu risc de mișcare trebuie să aibă spațiu rezervat.', 'Rezervă spațiu pentru embed-uri, bannere și elemente injectate ulterior.', 'Schimbările bruște de layout reduc controlul și pot întrerupe interacțiunile importante, mai ales pe mobil.', 'medium', 'technical', 'hard'),
            'schema_breadcrumbs' => self::def('Schema BreadcrumbList', 'Paginile structurale ar trebui să expună BreadcrumbList.', 'Adaugă ierarhia paginii în schemă.', 'Motorul de căutare înțelege mai greu locul paginii în site, iar utilizatorul primește mai puțin context în căutare.', 'low', 'technical', 'medium'),
            'schema_image_required_fields' => self::def('Imagini schemă complete', 'Imaginile din schemă ar trebui să includă url, width și height.', 'Completează dimensiunile imaginilor folosite în JSON-LD.', 'Schema trimite semnale mai slabe și poate pierde din consistența tehnică necesară pentru prezentări bogate.', 'low', 'technical', 'medium'),
            'local_tel_click' => self::def('Telefon click-to-call', 'Pagina locală trebuie să aibă link tel: pe numărul principal.', 'Transformă numărul principal într-un link de apel pentru mobil.', 'Pe căutări locale, pierzi conversii rapide atunci când vizitatorul nu poate suna imediat.', 'high', 'local_seo', 'easy'),
            'local_tel_prefix_local' => self::def('Telefon local valid', 'Numărul trebuie să fie românesc și recognoscibil local.', 'Corectează formatul numărului și afișează-l clar.', 'Un număr neclar sau nepotrivit pentru zonă reduce încrederea și poate confuza căutările locale.', 'medium', 'local_seo', 'easy'),
            'local_address_visible' => self::def('Adresă vizibilă', 'Afișează adresa completă în pagină pentru prezență locală.', 'Expune adresa în footer sau într-o secțiune de contact a paginii.', 'Fără adresă, pagina este mai puțin credibilă pentru căutări locale și pentru utilizatorii care vor să confirme locația.', 'high', 'local_seo', 'easy'),
            'local_directions_link' => self::def('Link de direcții', 'Ar trebui să existe link spre Google Maps / hărți.', 'Adaugă un buton clar către hartă sau direcții.', 'Elimini unul dintre cei mai rapizi pași către vizită sau apel atunci când cineva este deja decis să te contacteze.', 'high', 'local_seo', 'easy'),
            'local_opening_hours' => self::def('Program afișat', 'Programul trebuie afișat în text sau schemă.', 'Publică programul și menține-l actualizat.', 'Utilizatorii nu știu dacă pot apela sau veni, ceea ce scade încrederea exact în momentul conversiei.', 'high', 'local_seo', 'easy'),
            'local_schema_localbusiness' => self::def('Schema LocalBusiness', 'Paginile locale ar trebui să expună LocalBusiness sau un subtip relevant.', 'Adaugă schemă LocalBusiness cu datele reale ale afacerii.', 'Google primește prea puțin context local despre business, iar pagina concurează mai slab în căutările geografice.', 'high', 'local_seo', 'medium'),
            'local_schema_postal' => self::def('Schema PostalAddress', 'AddressLocality, streetAddress și restul câmpurilor trebuie completate.', 'Completează schema de adresă poștală cu date reale.', 'Locația devine mai greu de confirmat tehnic și apar mai puține șanse de corelare cu profilul business.', 'high', 'local_seo', 'medium'),
            'local_schema_tel' => self::def('Telefon în schemă', 'Proprietatea telephone trebuie să fie prezentă în LocalBusiness.', 'Adaugă numărul principal în schemă.', 'Semnalul local este mai incomplet și contactul principal este mai puțin clar pentru motoarele de căutare.', 'medium', 'local_seo', 'easy'),
            'local_schema_geo' => self::def('Coordonate geo', 'Schema locală ar trebui să includă latitude și longitude.', 'Adaugă coordonatele exacte ale locației.', 'Precizia semnalului local scade, mai ales pentru afaceri dependente de proximitate.', 'medium', 'local_seo', 'medium'),
            'local_schema_sameas' => self::def('sameAs / hasMap', 'Leagă pagina de profiluri și hartă prin sameAs sau hasMap.', 'Completează conexiunile către Google Maps și profiluri relevante.', 'Identitatea business-ului este mai greu de consolidat între site și prezența externă.', 'medium', 'local_seo', 'medium'),
            'local_schema_area' => self::def('Area served', 'Zonele deservite trebuie definite în schemă dacă activitatea nu este strict într-un singur loc.', 'Adaugă areaServed sau serviceArea pentru orașele relevante.', 'Pentru servicii mobile sau regionale, Google înțelege mai greu ce arie vrei să acoperi.', 'medium', 'local_seo', 'medium'),
            'local_schema_rating' => self::def('Rating în schemă', 'Recenziile reale pot fi expuse prin aggregateRating.', 'Mapează doar review-uri reale și verificabile.', 'Pierzi un semnal puternic de încredere locală dacă ai deja recenzii și nu le valorifici tehnic.', 'medium', 'local_seo', 'medium'),
            'local_city_detected' => self::def('Oraș prezent în pagină', 'Numele orașului trebuie să apară explicit în pagină.', 'Include orașul în zonele importante ale paginii dacă ea vizează local.', 'Fără oraș explicit, pagina nu comunică clar pentru ce zonă este relevantă.', 'high', 'local_seo', 'easy'),
            'local_city_in_title' => self::def('Oraș în title', 'Dacă pagina e locală, title-ul ar trebui să includă orașul.', 'Rescrie title-ul astfel încât să includă serviciul și orașul.', 'Listarea în Google concurează mai slab pe căutări locale, pentru că nu semnalează clar geografia încă din title.', 'high', 'local_seo', 'easy'),
            'local_city_in_h1' => self::def('Oraș în H1', 'H1-ul ar trebui să conțină zona atunci când pagina vizează căutări locale.', 'Include orașul și oferta în titlul principal.', 'Mesajul local al paginii este mai slab și conversia suferă pentru vizitatorii care caută confirmarea zonei.', 'high', 'local_seo', 'easy'),
            'local_city_in_slug' => self::def('Oraș în URL', 'Slug-ul ar trebui să includă orașul pentru pagini locale dedicate.', 'Folosește un slug descriptiv cu orașul, acolo unde structura site-ului permite.', 'URL-ul transmite mai puțin clar targetarea locală și poate fi mai puțin convingător în căutare.', 'medium', 'local_seo', 'hard'),
            'local_city_in_intro' => self::def('Oraș în introducere', 'Introducerea ar trebui să menționeze natural orașul.', 'Clarifică din primele rânduri pentru ce oraș sau zonă este pagina.', 'Primul contact cu pagina nu confirmă suficient de repede relevanța locală pentru vizitator.', 'medium', 'local_seo', 'easy'),
            'local_map_embed' => self::def('Hartă embed', 'Paginile locale pot include hartă embed pentru locație.', 'Adaugă o hartă doar dacă ajută realmente utilizatorul.', 'Lipsește un element vizual foarte clar pentru orientare și încredere locală.', 'low', 'local_seo', 'medium'),
            'local_alt_has_city' => self::def('ALT cu oraș', 'Cel puțin o imagine relevantă poate include orașul în ALT.', 'Folosește ALT descriptiv care include orașul doar acolo unde are sens.', 'Ratezi un mic semnal contextual care poate întări tema locală a paginii.', 'low', 'content', 'easy'),
            'local_locator' => self::def('Locator / pagini pe locații', 'Business-urile multi-locație ar trebui să aibă pagini dedicate sau locator.', 'Creează pagini separate pentru orașele importante sau un locator real.', 'Când deservești mai multe zone, lipsa unei structuri pe locații limitează scalarea organică locală.', 'medium', 'website_redesign', 'hard'),
            'local_whatsapp' => self::def('WhatsApp disponibil', 'Pentru lead-uri rapide pe mobil, un click-to-chat poate ajuta.', 'Adaugă un buton WhatsApp dacă este un canal real de vânzare.', 'Pierzi o cale foarte ușoară de contact pentru utilizatorii care nu vor să sune imediat.', 'low', 'local_seo', 'easy'),
        ];

        return $definitions;
    }

    public static function label(string $id): string
    {
        return self::get($id)['label'] ?? ('Check: ' . $id);
    }

    public static function rule(string $id): ?string
    {
        return self::get($id)['rule'] ?? null;
    }

    public static function tip(string $id, ?string $note = ''): string
    {
        return self::get($id)['tip'] ?? ('Revizuiește elementul ' . $id . '.');
    }

    public static function get(string $id): array
    {
        $definitions = self::definitions();
        return $definitions[$id] ?? [
            'label' => $id,
            'rule' => null,
            'tip' => 'Revizuiește acest element.',
            'business_impact_text' => 'Există o problemă care poate afecta vizibilitatea sau conversia acestei pagini.',
            'business_impact_magnitude' => 'medium',
            'related_service' => 'seo_optimization',
            'fix_complexity' => 'medium',
        ];
    }

    public static function enrichCheck(array $check): array
    {
        $id = (string)($check['id'] ?? '');
        $definition = self::get($id);

        return array_merge($check, $definition, [
            'id' => $id,
            'label' => $definition['label'],
            'rule' => $definition['rule'],
            'tip' => $definition['tip'],
            'business_impact_text' => $definition['business_impact_text'],
            'business_impact_magnitude' => $definition['business_impact_magnitude'],
            'related_service' => $definition['related_service'],
            'fix_complexity' => $definition['fix_complexity'],
        ]);
    }

    /**
     * @param array<int,array<string,mixed>> $checks
     * @return array<int,array<string,mixed>>
     */
    public static function enrichChecks(array $checks): array
    {
        return array_map(static fn(array $check): array => self::enrichCheck($check), $checks);
    }

    /**
     * @return array<string,string>
     */
    private static function def(
        string $label,
        string $rule,
        string $tip,
        string $businessImpactText,
        string $businessImpactMagnitude,
        string $relatedService,
        string $fixComplexity
    ): array {
        return [
            'label' => $label,
            'rule' => $rule,
            'tip' => $tip,
            'business_impact_text' => $businessImpactText,
            'business_impact_magnitude' => $businessImpactMagnitude,
            'related_service' => $relatedService,
            'fix_complexity' => $fixComplexity,
        ];
    }
}
