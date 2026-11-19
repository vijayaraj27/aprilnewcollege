<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Web\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportWordpressPages extends Command
{
    protected $signature = 'pages:import-wp
                            {--dry-run : List actions without writing to the database}
                            {--limit=0 : Limit number of pages processed after filtering (0 = all)}
                            {--only= : Comma-separated WP slugs to import}
                            {--skip-scrape : Use REST API content only (no HTML fallback)}
                            {--clean-footer-only : Strip footer/bgdesign/encoding artifacts from existing pages.description rows and exit}
                            {--fix-tabs : Convert WP tab markup in existing pages into stacked sections (and sync COE child pages)}';

    protected $description = 'Import page content from psr.edu.in WordPress REST API into pages table (skips department pages)';

    private string $baseUrl = 'https://psr.edu.in';

    /**
     * Map WordPress slug => local pages.slug when they differ.
     */
    private array $slugAliases = [
        'the-trust' => 'trust',
        'abouts_us' => 'about-us',
        'board-of-management' => 'governing-council',
        'bom-members' => 'members',
        'bom-meeting' => 'meeting',
        'academic-council-members' => 'members',
        'academic-council-meetings' => 'meetings',
        'finance-commitee-meetings' => 'meetings',
        'coe' => 'controller-of-exam',
        'grievance-redressal-system-2' => 'grievance-redressal-system',
        'centerofexcellence' => 'center-of-excellence',
        'anti-drugs-cells' => 'anti-drugs-club-committee',
        'incubation-center' => 'innovation-and-incubation-center-cell',
        'iiic-industry-institute-interaction-cell' => 'industry-institute-interaction-cell',
        'institutions-innovation-council' => 'institutions-innovation-council',
        'calendar-activities' => 'calendar-of-activities',
        'approval' => 'approval-affiliations',
        'support-help-desk' => 'help-desk',
        'suggestion' => 'suggestion-box',
        'pta' => 'parent-teacher-associationpta',
        'wec' => 'women-empowerment-cell',
        'edc' => 'entrepreneurship-cell',
        'idealab' => 'idea-lab',
        'idea-lab-chief-mentor' => 'chief-mentor',
        'idea-lab-faculty-coordinators' => 'faculty-coordinators',
        'idea-lab-steering-committee-members' => 'steering-committee-members',
        'idea-lab-tech-gurus' => 'tech-gurus',
        'idea-lab-student-ambassador' => 'student-ambassador',
        'idea-lab-events' => 'idea-lab-events',
        'idea-lab-internship' => 'internship',
        'messages' => 'principal',
        'college-policy' => 'policies-and-procedures',
        'achievement' => 'achievements',
        'fees-structure' => 'tuition-fees',
        'admission-apply-now' => 'admission',
        'admission' => 'admission',
        'about-us' => 'about-us',
        'form' => 'forms',
        'maintenance-cell' => 'maintenance',
    ];

    /**
     * Prefer a specific public URL when scraping (richer HTML than the WP slug link).
     */
    private array $scrapeUrlOverrides = [
        // abouts_us is an empty WP shell; real About HTML lives on /about-us/ (about.php template).
        'abouts_us' => 'https://psr.edu.in/about-us/',
        'about-us' => 'https://psr.edu.in/about-us/',
        'admission-apply-now' => 'https://psr.edu.in/admission/',
        'admission' => 'https://psr.edu.in/admission/',
    ];

    /**
     * WP pages whose body lives inside another page's tabs (filled via --fix-tabs / coe sync).
     */
    private array $tabChildSkipSlugs = [
        'coe-announcement',
    ];

    /**
     * When a WP page has tabs, map pane IDs to local page slugs.
     */
    private array $tabPaneLocalMap = [
        'coe' => [
            'coeannounce' => 'coe-announcements',
            'coefrm' => 'download-forms',
            'coeresults' => 'exam-results',
        ],
    ];

    /**
     * Utility / junk WP pages that should never be imported.
     */
    private array $skipSlugs = [
        'home',
        'search',
        'reset-password',
        'apply-now-form',
        'sitemap',
        'https-psr-resourz-net',
        '2-mark-cal',
        '2023-2',
        '14195-2',
        're_f',
        'naac-2',
        'naac-3',
        'feedback-form',
        'news_events',
        'newsletter',
        'newsletter-new',
        'find-alumni',
        'alumni-gallery',
        'alumni-careers',
        'alumni-achievement',
        'alumni-distinguished',
        'alumni-chapter',
        'alumni-students',
        'alumni',
        'events',
        'gallery',
        'webinar',
        'forum',
        'cells',
        'pg-programs',
        'ug-programs',
        'department',
        'coe-announcement',
    ];

    public function handle(): int
    {
        if ((bool) $this->option('clean-footer-only')) {
            return $this->cleanExistingFooters((bool) $this->option('dry-run'));
        }

        if ((bool) $this->option('fix-tabs')) {
            return $this->fixExistingTabPages((bool) $this->option('dry-run'));
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipScrape = (bool) $this->option('skip-scrape');
        $limit = (int) $this->option('limit');
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        $language = Language::where('default', 1)->first();
        if (!$language) {
            $this->error('Default language not found.');
            return Command::FAILURE;
        }

        $this->info('Fetching WordPress pages from ' . $this->baseUrl . ' ...');
        $wpPages = $this->fetchAllWpPages();
        if ($wpPages === null) {
            return Command::FAILURE;
        }

        $this->info('Fetched ' . count($wpPages) . ' WordPress pages.');

        $created = 0;
        $updated = 0;
        $skippedDept = 0;
        $skippedOther = 0;
        $empty = 0;
        $failed = 0;
        $processed = 0;

        foreach ($wpPages as $wp) {
            $wpSlug = $wp['slug'] ?? '';
            if ($wpSlug === '') {
                continue;
            }

            if (!empty($only) && !in_array($wpSlug, $only, true)) {
                continue;
            }

            if ($this->isDepartmentPage($wp)) {
                $skippedDept++;
                $this->line("  [SKIP-DEPT] {$wpSlug}");
                continue;
            }

            if (in_array($wpSlug, $this->skipSlugs, true) || in_array($wpSlug, $this->tabChildSkipSlugs, true)) {
                $skippedOther++;
                $this->line("  [SKIP] {$wpSlug}");
                continue;
            }

            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $localSlug = $this->slugAliases[$wpSlug] ?? $wpSlug;
            if ($this->isLocalDepartmentSlug($localSlug)) {
                $skippedDept++;
                $this->line("  [SKIP-DEPT-LOCAL] {$wpSlug} => {$localSlug}");
                continue;
            }

            $title = html_entity_decode(strip_tags($wp['title']['rendered'] ?? $wpSlug), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = trim(preg_replace('/\s+/', ' ', $title));

            $content = trim($wp['content']['rendered'] ?? '');
            $source = 'api';
            $link = $this->scrapeUrlOverrides[$wpSlug]
                ?? ($wp['link'] ?? ($this->baseUrl . '/' . $wpSlug . '/'));

            $needsScrape = !$this->hasMeaningfulContent($content)
                || $this->hasUnresolvedLazyImages($content)
                || isset($this->tabPaneLocalMap[$wpSlug])
                || isset($this->scrapeUrlOverrides[$wpSlug]);

            if (!$skipScrape && $needsScrape) {
                $scraped = $this->scrapePageContent($link);
                if ($this->hasMeaningfulContent($scraped)) {
                    $content = $scraped;
                    $source = 'scrape';
                }
            }

            $tabPanes = $this->extractTabPanes($content);
            $content = $this->convertTabsToSections($content);
            $content = $this->stripFooterNoise($content);

            if (!$this->hasMeaningfulContent($content)) {
                $empty++;
                $this->warn("  [EMPTY] {$wpSlug}");
                continue;
            }

            $metaTitle = $wp['yoast_head_json']['title'] ?? $title;
            $metaDescription = $wp['yoast_head_json']['og_description']
                ?? $wp['yoast_head_json']['description']
                ?? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 160, '');

            $payload = [
                'language_id' => $language->id,
                'title' => $this->uniqueTitle($title, $localSlug),
                'description' => $content,
                'meta_title' => is_string($metaTitle) ? Str::limit($metaTitle, 250, '') : $title,
                'meta_description' => is_string($metaDescription) ? $metaDescription : null,
                'status' => 1,
            ];

            $existing = Page::where('slug', $localSlug)->first();
            $action = $existing ? 'UPDATE' : 'CREATE';

            if ($dryRun) {
                $this->line("  [DRY-{$action}] {$wpSlug} => {$localSlug} ({$source}, " . strlen($content) . ' bytes)');
                if (!empty($tabPanes)) {
                    $this->syncTabChildPages($wpSlug, $tabPanes, $language->id, true);
                }
            } else {
                try {
                    if ($existing) {
                        // Keep existing title if unique constraint would collide; always refresh body/meta.
                        $update = $payload;
                        if ($existing->title !== $payload['title']
                            && Page::where('title', $payload['title'])->where('id', '!=', $existing->id)->exists()) {
                            unset($update['title']);
                        }
                        $existing->update($update);
                        $updated++;
                    } else {
                        Page::create(array_merge($payload, ['slug' => $localSlug]));
                        $created++;
                    }
                    $this->info("  [{$action}] {$wpSlug} => {$localSlug} ({$source})");
                    if (!empty($tabPanes)) {
                        $this->syncTabChildPages($wpSlug, $tabPanes, $language->id, false);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("  [FAIL] {$wpSlug}: " . $e->getMessage());
                    continue;
                }
            }

            $processed++;
            usleep(150000);
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['WP pages fetched', count($wpPages)],
                ['Skipped (department)', $skippedDept],
                ['Skipped (other)', $skippedOther],
                ['Empty content', $empty],
                ['Created', $dryRun ? 'n/a (dry-run)' : $created],
                ['Updated', $dryRun ? 'n/a (dry-run)' : $updated],
                ['Failed', $failed],
                ['Processed', $processed],
            ]
        );

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function fetchAllWpPages(): ?array
    {
        $all = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::timeout(60)
                ->acceptJson()
                ->get($this->baseUrl . '/wp-json/wp/v2/pages', [
                    'per_page' => $perPage,
                    'page' => $page,
                    '_fields' => 'id,slug,link,title,content,template,yoast_head_json',
                ]);

            if (!$response->successful()) {
                $this->error("WordPress API request failed (HTTP {$response->status()}) on page {$page}.");
                return null;
            }

            $chunk = $response->json();
            if (!is_array($chunk)) {
                $this->error('Invalid JSON from WordPress API.');
                return null;
            }

            $all = array_merge($all, $chunk);
            $totalPages = (int) $response->header('X-WP-TotalPages', 1);
            $page++;
        } while ($page <= $totalPages);

        return $all;
    }

    private function isDepartmentPage(array $wp): bool
    {
        $slug = strtolower((string) ($wp['slug'] ?? ''));
        $template = strtolower((string) ($wp['template'] ?? ''));

        // Keep Idea Lab "Department Coordinators" and Civil Maintenance cell.
        if (in_array($slug, ['department-coordinators', 'civil-maintenance'], true)) {
            return false;
        }

        if ($slug === 'department' || $slug === 'departments') {
            return true;
        }

        if (str_contains($template, 'department')) {
            return true;
        }

        if (preg_match('/-(ug|pg)$/', $slug)) {
            return true;
        }

        $departmentSlugs = [
            'artificial-intelligence-data-science-ai-ds',
            'bio-medical-ug',
            'bio-technology',
            'civil-engineering',
            'computer-science-and-engineering',
            'computer-science-and-engineering-pg',
            'computer-science-and-engineering-pgold',
            'electrical-and-electronics-engineering',
            'electronics-communication-engineering',
            'information-technology',
            'mechanical-engineering',
            'humanities-and-sciences-ug',
            'applied-electronics',
            'engineering-design-ed',
            'master-of-business-administration',
            'power-electronics-and-drives',
            'structural-engineering',
        ];
        if (in_array($slug, $departmentSlugs, true)) {
            return true;
        }

        // Department inner / supporting pages.
        $innerPatterns = [
            'departmental-faculty',
            'faculty-list-of-first-year',
            'academic-audit-report-',
            'time-table-for-all-sem',
            'time-table-of-first-year',
            'list-of-students-who-cleared',
            'student-list-for-all-four',
            'consultancy-from-industries-',
            'sponsored-research-projects',
            'placement-details-list-',
            'placement-details-civil',
            'laboratory-records-manuals-',
            'course-files-',
            'project-report-mechanical',
            'video-of-lab-experiments',
            'video-tour-of-mech-dept',
            'faculty-research-papers-with-online-links',
        ];
        foreach ($innerPatterns as $pattern) {
            if (str_contains($slug, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isLocalDepartmentSlug(string $slug): bool
    {
        $departmentLocal = [
            'department',
            'ug-programs',
            'pg',
            'artificial-intelligence-and-data-science-ai-and-ds',
            'bio-medicalbm',
            'bio-technology-bt',
            'civil-engineering-civil',
            'computer-science-and-engineering-cse',
            'electrical-and-electronics-engineering-eee',
            'electronics-and-communication-engineering-ece',
            'information-technology-it',
            'mechanical-engineering-mech',
            'science-and-humanities-sandh',
            'applied-electronics-ae',
            'engineering-design-ed',
            'master-of-business-administration-mba',
            'power-electronics-and-drives-ped',
            'structural-engineering-se',
        ];

        return in_array($slug, $departmentLocal, true);
    }

    private function hasMeaningfulContent(string $html): bool
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if (strlen($text) >= 40) {
            return true;
        }

        // Link-only pages (e.g. NIRF PDF lists) still count as content.
        $linkCount = preg_match_all('/<a\s[^>]*href=["\'][^"\']+/i', $html);
        return $linkCount >= 1 && strlen($text) >= 5;
    }

    /**
     * True when images are still WP Smush placeholders (or empty src) and need a live scrape.
     */
    private function hasUnresolvedLazyImages(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        if (stripos($html, 'data:image/svg') !== false) {
            return true;
        }

        if (preg_match('/<img\b[^>]*\bsrc=["\']\s*["\']/i', $html)) {
            return true;
        }

        return stripos($html, 'data-src=') !== false && stripos($html, 'lazyload') !== false;
    }

    private function scrapePageContent(string $url): string
    {
        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'User-Agent' => 'PSR-College-Importer/1.0',
                    'Accept' => 'text/html',
                ])
                ->get($url);

            if (!$response->successful()) {
                return '';
            }

            $html = $response->body();
            return $this->extractMainHtml($html);
        } catch (\Throwable $e) {
            $this->warn('    scrape failed: ' . $e->getMessage());
            return '';
        }
    }

    private function extractMainHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        if (!$loaded) {
            return '';
        }

        $xpath = new \DOMXPath($dom);

        // Drop global chrome before selecting content.
        $this->removeNodesByXPath($xpath, [
            '//footer',
            '//*[contains(concat(" ", normalize-space(@class), " "), " footer ")]',
            '//*[contains(@class, "footer-top")]',
            '//*[contains(@class, "footer-bottom")]',
            '//*[contains(@class, "site-footer")]',
            '//*[contains(@class, "copy-right")]',
            '//*[contains(@class, "copyright")]',
            '//*[contains(@class, "stay-connected")]',
            '//*[contains(@class, "abt-bgdesign")]',
            '//*[contains(@class, "common-bgdesign")]',
            '//*[@id="footer"]',
            '//nav',
            '//header',
        ]);

        $selectors = [
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' abt-content ') and contains(concat(' ', normalize-space(@class), ' '), ' common-content ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' common-content ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' history-wrap ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' admission-wrap ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' vismis-wrap ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]",
            "//article",
        ];

        $node = null;
        foreach ($selectors as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                // Prefer the smallest non-empty candidate to avoid wrapping chrome.
                foreach ($nodes as $candidate) {
                    $text = trim(preg_replace('/\s+/', ' ', $candidate->textContent ?? ''));
                    if (strlen($text) >= 5) {
                        $node = $candidate;
                        break 2;
                    }
                }
            }
        }

        if (!$node) {
            return '';
        }

        // Decorative WP theme chrome inside the content wrapper.
        $nestedXpath = new \DOMXPath($dom);
        $this->removeNodesByXPath($nestedXpath, [
            './/*[contains(@class, "abt-bgdesign")]',
            './/*[contains(@class, "common-bgdesign")]',
        ], $node);

        foreach (['script', 'style', 'noscript', 'iframe', 'form'] as $tag) {
            $remove = [];
            foreach ($node->getElementsByTagName($tag) as $el) {
                $remove[] = $el;
            }
            foreach ($remove as $el) {
                if ($el->parentNode) {
                    $el->parentNode->removeChild($el);
                }
            }
        }

        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }

        $inner = preg_replace('/\sdata-cursor-ref="[^"]*"/', '', $inner);
        return trim($inner);
    }

    private function removeNodesByXPath(\DOMXPath $xpath, array $queries, ?\DOMNode $context = null): void
    {
        foreach ($queries as $query) {
            $nodes = $context
                ? $xpath->query($query, $context)
                : $xpath->query($query);
            if (!$nodes) {
                continue;
            }
            $toRemove = [];
            foreach ($nodes as $node) {
                $toRemove[] = $node;
            }
            foreach ($toRemove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    /**
     * @return array<string, array{title:string,html:string}>
     */
    private function extractTabPanes(string $html): array
    {
        if ($html === '' || stripos($html, 'tab-pane') === false) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        if (!$loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $panes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' tab-pane ')]");
        if (!$panes || $panes->length === 0) {
            return [];
        }

        $result = [];
        foreach ($panes as $pane) {
            /** @var \DOMElement $pane */
            $id = trim((string) $pane->getAttribute('id'));
            if ($id === '') {
                continue;
            }

            $title = '';
            $titleFromHeading = false;
            foreach (['h2', 'h3', 'h4'] as $tag) {
                $heading = $pane->getElementsByTagName($tag)->item(0);
                if ($heading) {
                    $candidate = trim(preg_replace('/\s+/', ' ', $heading->textContent ?? ''));
                    if ($candidate !== '') {
                        $title = $candidate;
                        $titleFromHeading = true;
                        break;
                    }
                }
            }
            if ($title === '') {
                $title = Str::title(str_replace(['-', '_'], ' ', $id));
            }

            $inner = '';
            foreach ($pane->childNodes as $child) {
                $inner .= $dom->saveHTML($child);
            }
            $inner = $this->stripFooterNoise(trim($inner));
            if (!$this->hasMeaningfulContent($inner)) {
                continue;
            }

            $result[$id] = [
                'title' => $title,
                'title_from_heading' => $titleFromHeading,
                'html' => $inner,
            ];
        }

        return $result;
    }

    private function convertTabsToSections(string $html): string
    {
        $panes = $this->extractTabPanes($html);
        if (empty($panes)) {
            // Still strip empty tab nav if present without panes.
            return $html;
        }

        $sectionsHtml = '';
        $singlePane = count($panes) === 1;
        foreach ($panes as $pane) {
            $sectionsHtml .= '<section class="wp-tab-section mb-5">';
            // Skip bogus titles derived only from pane ids (e.g. "Nba" wrapping NSS/RRC/YRC).
            $showTitle = !$singlePane && !empty($pane['title_from_heading']);
            if ($showTitle) {
                $sectionsHtml .= '<h3 class="wp-tab-section-title">' . e($pane['title']) . '</h3>';
            }
            $sectionsHtml .= '<div class="wp-tab-section-body">' . $pane['html'] . '</div>';
            $sectionsHtml .= '</section>';
        }

        return $this->stripFooterNoise($sectionsHtml);
    }

    private function syncTabChildPages(string $wpSlug, array $panes, int $languageId, bool $dryRun): void
    {
        $map = $this->tabPaneLocalMap[$wpSlug] ?? [];
        if (empty($map)) {
            return;
        }

        foreach ($map as $paneId => $localSlug) {
            if (!isset($panes[$paneId])) {
                $this->warn("  [TAB-MISS] {$wpSlug}#{$paneId} => {$localSlug}");
                continue;
            }

            $pane = $panes[$paneId];
            $html = '<section class="wp-tab-section"><h3>' . e($pane['title']) . '</h3><div class="wp-tab-section-body">'
                . $pane['html']
                . '</div></section>';

            if ($dryRun) {
                $this->line("  [DRY-TAB] {$paneId} => {$localSlug} (" . strlen($html) . ' bytes)');
                continue;
            }

            $page = Page::where('slug', $localSlug)->first();
            if ($page) {
                $page->update([
                    'description' => $html,
                    'status' => 1,
                ]);
                $this->info("  [TAB-UPDATE] {$paneId} => {$localSlug}");
            } else {
                Page::create([
                    'language_id' => $languageId,
                    'title' => $this->uniqueTitle($pane['title'], $localSlug),
                    'slug' => $localSlug,
                    'description' => $html,
                    'meta_title' => $pane['title'] . ' - PSR Engineering College',
                    'meta_description' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($pane['html']))), 160, ''),
                    'status' => 1,
                ]);
                $this->info("  [TAB-CREATE] {$paneId} => {$localSlug}");
            }
        }
    }

    private function fixExistingTabPages(bool $dryRun): int
    {
        $this->info('Fixing tabbed pages (stacked sections + COE child sync)...');
        $language = Language::where('default', 1)->first();
        if (!$language) {
            $this->error('Default language not found.');
            return Command::FAILURE;
        }

        $fixed = 0;
        $checked = 0;

        foreach ($this->tabPaneLocalMap as $wpSlug => $map) {
            $url = $this->baseUrl . '/' . $wpSlug . '/';
            $this->line("  Fetching {$url}");
            $raw = $this->scrapePageContent($url);
            // scrapePageContent currently returns already-stripped main html; ensure panes survive:
            // Re-fetch and extract with a dedicated raw helper if panes missing.
            $panes = $this->extractTabPanes($raw);
            if (empty($panes)) {
                $response = Http::timeout(45)->withHeaders([
                    'User-Agent' => 'PSR-College-Importer/1.0',
                    'Accept' => 'text/html',
                ])->get($url);
                if ($response->successful()) {
                    $raw = $this->extractMainHtmlKeepTabs($response->body());
                    $panes = $this->extractTabPanes($raw);
                }
            }

            if (empty($panes)) {
                $this->warn("  [NO-PANES] {$wpSlug}");
                continue;
            }

            $stacked = $this->convertTabsToSections($raw);
            $localSlug = $this->slugAliases[$wpSlug] ?? $wpSlug;
            $checked++;

            if ($dryRun) {
                $this->line("  [DRY] {$localSlug} panes=" . count($panes) . ' bytes=' . strlen($stacked));
                $this->syncTabChildPages($wpSlug, $panes, $language->id, true);
            } else {
                $page = Page::where('slug', $localSlug)->first();
                if ($page) {
                    $page->update(['description' => $stacked, 'status' => 1]);
                } else {
                    Page::create([
                        'language_id' => $language->id,
                        'title' => $this->uniqueTitle('Controller of Examinations', $localSlug),
                        'slug' => $localSlug,
                        'description' => $stacked,
                        'status' => 1,
                    ]);
                }
                $this->info("  [FIXED] {$localSlug}");
                $this->syncTabChildPages($wpSlug, $panes, $language->id, false);
            }
            $fixed++;
        }

        Page::query()->orderBy('id')->chunkById(50, function ($pages) use (&$fixed, &$checked, $dryRun) {
            foreach ($pages as $page) {
                if ($this->isLocalDepartmentSlug($page->slug)) {
                    continue;
                }
                $original = (string) $page->description;
                if (stripos($original, 'tab-pane') === false && stripos($original, 'nav-tabs') === false) {
                    continue;
                }
                $checked++;
                $converted = $this->convertTabsToSections($original);
                if ($converted === $original || !$this->hasMeaningfulContent($converted)) {
                    continue;
                }
                if ($dryRun) {
                    $this->line("  [DRY-CONVERT] {$page->slug}");
                } else {
                    $page->description = $converted;
                    $page->save();
                    $this->info("  [CONVERT] {$page->slug}");
                }
                $fixed++;
            }
        });

        $this->table(['Metric', 'Count'], [
            ['Checked', $checked],
            ['Fixed', $fixed],
            ['Mode', $dryRun ? 'dry-run' : 'write'],
        ]);

        return Command::SUCCESS;
    }

    private function extractMainHtmlKeepTabs(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        if (!$loaded) {
            return '';
        }

        $xpath = new \DOMXPath($dom);
        $this->removeNodesByXPath($xpath, [
            '//footer',
            '//*[contains(@class, "footer-top")]',
            '//*[contains(@class, "stay-connected")]',
            '//*[contains(@class, "abt-bgdesign")]',
            '//*[contains(@class, "common-bgdesign")]',
            '//header',
        ]);

        $tabWrappers = $xpath->query("//*[.//*[contains(concat(' ', normalize-space(@class), ' '), ' nav-tabs ')] and .//*[contains(concat(' ', normalize-space(@class), ' '), ' tab-content ')]]");
        $node = null;
        if ($tabWrappers && $tabWrappers->length > 0) {
            $best = null;
            $bestLen = PHP_INT_MAX;
            foreach ($tabWrappers as $candidate) {
                $text = trim(preg_replace('/\s+/', ' ', $candidate->textContent ?? ''));
                $len = strlen($text);
                if ($len >= 40 && $len < $bestLen) {
                    $best = $candidate;
                    $bestLen = $len;
                }
            }
            $node = $best;
        }

        if (!$node) {
            return $this->extractMainHtml($html);
        }

        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }

        return trim(preg_replace('/\sdata-cursor-ref="[^"]*"/', '', $inner) ?? $inner);
    }

    /**
     * Remove site footer / stay-connected / decorative WP theme chrome from imported HTML.
     */
    private function stripFooterNoise(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Decorative slanted SVG background from the live WP theme.
        $html = preg_replace(
            '/<div[^>]*class="[^"]*(?:abt-bgdesign|common-bgdesign)[^"]*"[^>]*>\s*(?:<div[^>]*>\s*)?<svg[\s\S]*?<\/svg>\s*(?:<\/div>\s*)?<\/div>/is',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/<div[^>]*class="[^"]*(?:abt-bgdesign|common-bgdesign)[^"]*"[^>]*>[\s\S]*?Path_7221[\s\S]*?<\/div>\s*<\/div>/is',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/<svg[^>]*>[\s\S]*?Path_7221[\s\S]*?<\/svg>/i',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/<svg[^>]*viewBox="862\s+-1978[^"]*"[^>]*>.*?<\/svg>/is',
            '',
            $html
        ) ?? $html;

        // Cut from common footer markers onward.
        $cutMarkers = [
            'Stay Connected',
            'Stay Connected :',
            'class="footer-top"',
            "class='footer-top'",
            'class="footer-bottom"',
            'class="foottop-strip"',
            'Designed by: webandcrafts',
            'Designed by:</',
            'Privacy Policy Cookie Policy Sitemap',
            '© PSR-2026',
            '&copy; PSR-2026',
            'P.S.R.ENGINEERING COLLEGE Sevalpatti, Sivakasi - 626140',
            'P.S.R.ENGINEERING COLLEGE
Sevalpatti, Sivakasi - 626140',
        ];

        $cutAt = null;
        foreach ($cutMarkers as $marker) {
            $pos = stripos($html, $marker);
            if ($pos !== false) {
                $cutAt = $cutAt === null ? $pos : min($cutAt, $pos);
            }
        }

        if ($cutAt !== null && $cutAt > 0) {
            // Back up to previous tag boundary to avoid leaving a half-open tag.
            $tagStart = strrpos(substr($html, 0, $cutAt), '<');
            if ($tagStart !== false && $tagStart > (int) ($cutAt * 0.5)) {
                $cutAt = $tagStart;
            }
            $html = substr($html, 0, $cutAt);
        }

        // Remove leftover footer-ish blocks if markers were nested oddly.
        $html = preg_replace('/<(section|div|footer)[^>]*(footer|stay-connected|copyright|copy-right)[^>]*>.*?<\/\1>/is', '', $html) ?? $html;

        // Address/contact footer block often appears as plain text after content.
        $html = preg_replace(
            '/P\.?\s*S\.?\s*R\.?\s*ENGINEERING COLLEGE\s*Sevalpatti[\s\S]*?(?:webandcrafts\.com|Sitemap)\s*/i',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/Phone:\s*80125[\s\S]*?contact@psr\.edu\.in/i',
            '',
            $html
        ) ?? $html;

        // Leftover WP theme modals scraped into page body.
        $modalMarkers = [
            '<!--eventModal-->',
            'id="eventModal"',
            'id="feedmodal"',
            'id="apply_now"',
            'id="Alumni_login"',
            'id="Alumni_register"',
            'id="job-add-cmnt"',
            'class="scrolltop"',
        ];
        foreach ($modalMarkers as $marker) {
            $pos = stripos($html, $marker);
            if ($pos !== false && $pos > 0) {
                $html = substr($html, 0, $pos);
                break;
            }
        }

        $html = $this->fixLazyLoadedImages($html);
        $html = $this->stripDecorativeSvgs($html);

        return trim($this->sanitizeEncodingArtifacts($html));
    }

    /**
     * Drop WordPress theme illustration SVGs (portal/intro/icons). Keep real assets such as the college logo and org chart.
     */
    private function stripDecorativeSvgs(string $html): string
    {
        if ($html === '' || stripos($html, '.svg') === false) {
            return $html;
        }

        $keep = ['college-logo.svg', 'org-chart.svg'];

        $html = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) use ($keep) {
            $tag = $matches[0];
            if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $src)) {
                return $tag;
            }
            if (!preg_match('/\.svg(\?|#|$)/i', $src[1])) {
                return $tag;
            }
            foreach ($keep as $name) {
                if (stripos($src[1], $name) !== false) {
                    return $tag;
                }
            }

            return '';
        }, $html) ?? $html;

        $html = preg_replace('/<div class="img-wrap">\s*<\/div>/i', '', $html) ?? $html;

        return $html;
    }

    /**
     * WP Smush/lazyload stores the real URL in data-src and a 1x1 SVG in src.
     * Without the WP lazyload script those images never appear — promote data-src to src.
     */
    private function fixLazyLoadedImages(string $html): string
    {
        if ($html === '' || (stripos($html, 'data-src') === false && stripos($html, 'lazyload') === false && stripos($html, 'data:image/') === false)) {
            return $html;
        }

        return preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) {
            $tag = $matches[0];

            $read = static function (string $tag, string $name): ?string {
                if (preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*"([^"]*)"/i', $tag, $m)) {
                    return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                if (preg_match("/\\b" . preg_quote($name, '/') . "\\s*=\\s*'([^']*)'/i", $tag, $m)) {
                    return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                return null;
            };

            $realSrc = $read($tag, 'data-src')
                ?? $read($tag, 'data-lazy-src')
                ?? $read($tag, 'data-original');
            $src = $read($tag, 'src');

            $isPlaceholder = $src === null
                || $src === ''
                || str_starts_with($src, 'data:image/')
                || str_contains(strtolower($src), 'placeholder')
                || str_contains(strtolower($src), 'blank.gif');

            if (!$realSrc && $src && !$isPlaceholder) {
                $realSrc = $src;
            }

            if (!$realSrc) {
                $altHint = strtolower($read($tag, 'alt') ?? '');
                if (str_contains($altHint, 'organizational chart')) {
                    $realSrc = 'https://psr.edu.in/wp-content/uploads/2019/01/org-chart.svg';
                } elseif (str_contains($altHint, 'logo - psr') || $altHint === 'logo') {
                    $realSrc = 'https://psr.edu.in/wp-content/uploads/2019/01/college-logo.svg';
                }
            }

            if (!$realSrc) {
                // Drop unrecoverable placeholders so they don't reserve blank space.
                if ($isPlaceholder) {
                    return '';
                }

                return $tag;
            }

            if (str_starts_with($realSrc, 'http://psr.edu.in')) {
                $realSrc = 'https://' . substr($realSrc, strlen('http://'));
            }

            $srcset = $read($tag, 'data-srcset') ?? $read($tag, 'data-lazy-srcset') ?? $read($tag, 'srcset');
            if ($srcset) {
                $srcset = str_replace('http://psr.edu.in', 'https://psr.edu.in', $srcset);
            }
            $sizes = $read($tag, 'data-sizes') ?? $read($tag, 'sizes');
            $alt = $read($tag, 'alt') ?? '';
            $width = $read($tag, 'width');
            $height = $read($tag, 'height');
            $class = $read($tag, 'class');
            if ($class !== null) {
                $class = trim(preg_replace('/\blazyload\b/i', '', $class) ?? $class);
                $class = trim(preg_replace('/\s+/', ' ', $class) ?? $class);
            }
            $decoding = $read($tag, 'decoding');
            $loading = $read($tag, 'loading');

            $parts = ['src="' . htmlspecialchars($realSrc, ENT_QUOTES, 'UTF-8') . '"'];
            if ($alt !== null) {
                $parts[] = 'alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($width) {
                $parts[] = 'width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($height) {
                $parts[] = 'height="' . htmlspecialchars($height, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($class) {
                $parts[] = 'class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($srcset) {
                $parts[] = 'srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($sizes) {
                $parts[] = 'sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($decoding) {
                $parts[] = 'decoding="' . htmlspecialchars($decoding, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($loading) {
                $parts[] = 'loading="' . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8') . '"';
            }

            return '<img ' . implode(' ', $parts) . '>';
        }, $html) ?? $html;
    }

    /**
     * Fix common UTF-8 / Windows-1252 mojibake (e.g. "Â 1", curly-quote garbage).
     */
    private function sanitizeEncodingArtifacts(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $replacements = [
            "\xC2\xA0" => ' ', // real UTF-8 non-breaking space
            'Â ' => ' ',
            'Â ' => ' ',
            'Â­' => '',
            'Â' => '',
            'â€™' => "'",
            'â€˜' => "'",
            'â€œ' => '"',
            'â€' => '"',
            'â€“' => '–',
            'â€”' => '—',
            'â€¦' => '…',
            'â€¢' => '•',
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ã¡' => 'á',
            'Ã ' => 'à',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ãº' => 'ú',
            'Ã±' => 'ñ',
            'Ã¼' => 'ü',
            'Ã¶' => 'ö',
            'Ã¤' => 'ä',
            '&Acirc; ' => ' ',
            '&Acirc;&nbsp;' => ' ',
            '&Acirc;' => '',
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // Any leftover "Â" glued to digits/text (e.g. "Â1", "ÂThe").
        $html = preg_replace('/Â(?=[\dA-Za-z])/u', '', $html) ?? $html;
        $html = preg_replace('/\x{00A0}/u', ' ', $html) ?? $html;
        $html = preg_replace('/[ \t]{2,}/', ' ', $html) ?? $html;

        return $html;
    }

    private function cleanExistingFooters(bool $dryRun): int
    {
        $this->info('Cleaning footer/bgdesign/encoding artifacts from existing pages...');
        $updated = 0;
        $checked = 0;

        Page::query()->orderBy('id')->chunkById(50, function ($pages) use (&$updated, &$checked, $dryRun) {
            foreach ($pages as $page) {
                $checked++;
                $original = (string) $page->description;
                $cleaned = $this->stripFooterNoise($original);
                if ($cleaned === $original) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [DRY] {$page->slug} " . strlen($original) . ' => ' . strlen($cleaned));
                } else {
                    $page->description = $cleaned;
                    $page->save();
                    $this->info("  [CLEANED] {$page->slug}");
                }
                $updated++;
            }
        });

        $this->table(['Metric', 'Count'], [
            ['Checked', $checked],
            ['Cleaned', $updated],
            ['Mode', $dryRun ? 'dry-run' : 'write'],
        ]);

        return Command::SUCCESS;
    }

    private function uniqueTitle(string $title, string $slug): string
    {
        $base = $title !== '' ? $title : Str::title(str_replace('-', ' ', $slug));
        $candidate = $base;
        $i = 2;

        while (Page::where('title', $candidate)->where('slug', '!=', $slug)->exists()) {
            $candidate = $base . ' (' . $i . ')';
            $i++;
        }

        return Str::limit($candidate, 190, '');
    }
}
