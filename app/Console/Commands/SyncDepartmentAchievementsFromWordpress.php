<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Language;
use App\Models\Web\AchievementItem;
use App\Models\Web\Achievements;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncDepartmentAchievementsFromWordpress extends Command
{
    protected $signature = 'achievements:sync-from-wordpress
                            {--department= : Local department id to sync}
                            {--dry-run : Parse and report without writing}
                            {--skip-images : Do not download achievement images}';

    protected $description = 'Sync department achievements from psr.edu.in (WP page discovery + HTML parse)';

    private string $baseUrl = 'https://psr.edu.in';

    /**
     * Local department slug => WordPress page slug
     */
    private array $slugMap = [
        'artificial-intelligence-data-science' => 'artificial-intelligence-data-science-ai-ds',
        'biomedical-engineering' => 'bio-medical-ug',
        'biotechnology' => 'bio-technology',
        'civil-engineering' => 'civil-engineering',
        'computer-science-engineering' => 'computer-science-and-engineering',
        'electrical-electronics-engineering' => 'electrical-and-electronics-engineering',
        'electronics-communication-engineering' => 'electronics-communication-engineering',
        'information-technology' => 'information-technology',
        'mechanical-engineering' => 'mechanical-engineering',
        'science-humanities' => 'humanities-and-sciences-ug',
        'master-of-business-administration' => 'master-of-business-administration',
        'structural-engineering' => 'structural-engineering',
        'engineering-design' => 'engineering-design-ed',
        'power-electronics-drives' => 'power-electronics-and-drives',
        'applied-electronics' => 'applied-electronics',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipImages = (bool) $this->option('skip-images');
        $departmentFilter = $this->option('department');
        $languageId = Language::version()->id ?? 1;

        $query = Department::query()->where('status', 1)->orderBy('id');
        if ($departmentFilter) {
            $query->where('id', $departmentFilter);
        }
        $departments = $query->get();

        if ($departments->isEmpty()) {
            $this->error('No departments found.');
            return Command::FAILURE;
        }

        $uploadPath = public_path('uploads/achievement-items/');
        if (!$dryRun && !File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $totals = [
            'staff' => 0,
            'student' => 0,
            'student-table' => 0,
            'student-appreciation' => 0,
            'images' => 0,
            'failed' => 0,
        ];

        foreach ($departments as $department) {
            $wpSlug = $this->slugMap[$department->slug] ?? null;
            if (!$wpSlug) {
                $this->warn("Skip dept {$department->id} ({$department->slug}): no WP slug map");
                continue;
            }

            $this->info("Syncing {$department->title} [{$department->id}] via WP slug {$wpSlug}...");

            try {
                $page = $this->fetchWpPage($wpSlug);
                if (!$page) {
                    $this->error("  WP page not found for {$wpSlug}");
                    $totals['failed']++;
                    continue;
                }

                $pageUrl = $page['link'] ?? ($this->baseUrl . '/ug-programs/' . $wpSlug . '/');
                $pageUrl = str_replace('http://psr.edu.in', 'https://psr.edu.in', $pageUrl);

                $html = $this->fetchHtml($pageUrl);
                if ($html === null) {
                    $this->error("  Failed to fetch HTML: {$pageUrl}");
                    $totals['failed']++;
                    continue;
                }

                $parsed = $this->parseAchievements($html);
                $this->line(sprintf(
                    '  Staff: %d | Student: %d | Table: %d | Appreciation: %d',
                    count($parsed['staff']),
                    count($parsed['student']),
                    count($parsed['student-table']),
                    count($parsed['student-appreciation'])
                ));

                if ($dryRun) {
                    $totals['staff'] += count($parsed['staff']);
                    $totals['student'] += count($parsed['student']);
                    $totals['student-table'] += count($parsed['student-table']);
                    $totals['student-appreciation'] += count($parsed['student-appreciation']);
                    continue;
                }

                if (!$skipImages) {
                    foreach ($parsed['student'] as &$row) {
                        if (!empty($row['image_url'])) {
                            $saved = $this->downloadImage($row['image_url'], $department->id, $row['title'] ?? 'student');
                            if ($saved) {
                                $row['image'] = $saved;
                                $totals['images']++;
                            }
                        }
                    }
                    unset($row);
                }

                $this->writeType($department->id, $languageId, 'staff', $parsed['staff'], [
                    'description',
                ]);
                $this->writeType($department->id, $languageId, 'student', $parsed['student'], [
                    'year', 'title', 'description', 'image',
                ]);
                $this->writeType($department->id, $languageId, 'student-table', $parsed['student-table'], [
                    'year', 'student_name', 'event_nature', 'event_name', 'period_date', 'organized_by', 'awards',
                ]);
                $this->writeType($department->id, $languageId, 'student-appreciation', $parsed['student-appreciation'], [
                    'year', 'student_name', 'year_of_student', 'event_name', 'project_name', 'supervisor', 'result_status',
                ]);

                if (!empty($parsed['basic_description']) || !empty($parsed['basic_title'])) {
                    $this->updateBasic($department->id, $parsed['basic_title'], $parsed['basic_description']);
                }

                $totals['staff'] += count($parsed['staff']);
                $totals['student'] += count($parsed['student']);
                $totals['student-table'] += count($parsed['student-table']);
                $totals['student-appreciation'] += count($parsed['student-appreciation']);
            } catch (\Throwable $e) {
                $totals['failed']++;
                $this->error('  Error: ' . $e->getMessage());
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Staff rows', $totals['staff']],
                ['Student rows', $totals['student']],
                ['Table rows', $totals['student-table']],
                ['Appreciation rows', $totals['student-appreciation']],
                ['Images downloaded', $totals['images']],
                ['Failed departments', $totals['failed']],
                ['Mode', $dryRun ? 'dry-run' : 'write'],
            ]
        );

        return $totals['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function writeType(int $departmentId, int $languageId, string $type, array $rows, array $fields): void
    {
        if (empty($rows)) {
            $this->warn("  No {$type} rows parsed; keeping existing.");
            return;
        }

        AchievementItem::where('department_id', $departmentId)->where('type', $type)->delete();

        foreach ($rows as $i => $row) {
            $payload = [
                'language_id' => $languageId,
                'department_id' => $departmentId,
                'type' => $type,
                'sort_order' => $i + 1,
                'status' => 1,
            ];
            foreach ($fields as $field) {
                $payload[$field] = $row[$field] ?? null;
            }
            AchievementItem::create($payload);
        }
    }

    private function updateBasic(int $departmentId, ?string $title, ?string $description): void
    {
        $row = Achievements::firstOrNew(['departmentId' => $departmentId]);
        if (!$row->exists) {
            $row->designationId = 1;
        }
        if ($title) {
            $row->title = $title;
        }
        if ($description) {
            $row->description = $description;
        }
        $row->save();
    }

    private function fetchWpPage(string $slug): ?array
    {
        $response = Http::timeout(60)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'PSRCollegeAchievementsSync/1.0'])
            ->get($this->baseUrl . '/wp-json/wp/v2/pages', [
                'slug' => $slug,
                'per_page' => 1,
                '_fields' => 'id,slug,link,title,template',
            ]);

        if (!$response->successful()) {
            return null;
        }

        $json = $response->json();
        return is_array($json) && !empty($json[0]) ? $json[0] : null;
    }

    private function fetchHtml(string $url): ?string
    {
        $response = Http::timeout(90)
            ->withHeaders(['User-Agent' => 'PSRCollegeAchievementsSync/1.0'])
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * @return array{
     *   staff: array<int,array>,
     *   student: array<int,array>,
     *   student-table: array<int,array>,
     *   student-appreciation: array<int,array>,
     *   basic_title: ?string,
     *   basic_description: ?string
     * }
     */
    private function parseAchievements(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $result = [
            'staff' => [],
            'student' => [],
            'student-table' => [],
            'student-appreciation' => [],
            'basic_title' => 'Achievements',
            'basic_description' => null,
        ];

        $staffStart = $this->findOwnTextNode($xpath, fn ($t) => $t === 'staff achievements', true);
        $studentStart = $this->findOwnTextNode($xpath, fn ($t) => $t === 'student achievements', true);
        $placementStart = $this->findOwnTextNode($xpath, fn ($t) => $t === 'placement' || str_starts_with($t, 'placement'), true);
        $introStart = $this->findOwnTextNode($xpath, fn ($t) => $t === 'achievements', true);

        if ($introStart) {
            $result['basic_description'] = $this->collectIntroDescription($introStart, $staffStart ?: $studentStart);
        }

        if ($staffStart) {
            $result['staff'] = $this->collectListItemsUntil($staffStart, $studentStart ?: $placementStart);
        }

        $result['student-appreciation'] = $this->parseAppreciationTables($xpath);
        $result['student-table'] = $this->parseStudentEventTables($xpath);

        if ($studentStart) {
            $result['student'] = $this->collectStudentNarrative($xpath, $studentStart, $placementStart);
        }

        return $result;
    }

    private function findOwnTextNode(\DOMXPath $xpath, callable $matcher, bool $headingsOnly = false): ?\DOMNode
    {
        $query = $headingsOnly
            ? '//h1|//h2|//h3|//h4|//h5|//h6'
            : '//h1|//h2|//h3|//h4|//h5|//h6|//p|//strong|//b|//span';
        $candidates = $xpath->query($query);
        foreach ($candidates as $node) {
            $text = Str::lower($this->ownText($node));
            if ($text !== '' && $matcher($text)) {
                return $node;
            }
        }

        return null;
    }

    private function ownText(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE && in_array(Str::lower($child->nodeName), ['span', 'strong', 'b', 'em', 'i', 'a'], true)) {
                $text .= $child->textContent;
            }
        }

        return $this->normalizeText($text);
    }

    private function collectIntroDescription(\DOMNode $start, ?\DOMNode $stop): ?string
    {
        $parts = [];
        for ($node = $start->nextSibling; $node; $node = $node->nextSibling) {
            if ($stop && $node->isSameNode($stop)) {
                break;
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $name = Str::lower($node->nodeName);
            if (in_array($name, ['h1', 'h2', 'h3', 'h4', 'h5'], true)) {
                break;
            }
            if (in_array($name, ['p', 'div'], true)) {
                $text = $this->normalizeText($node->textContent);
                if ($text !== '' && !str_contains(Str::lower($text), 'staff achievement')) {
                    $parts[] = $text;
                }
            }
            if (count($parts) >= 2) {
                break;
            }
        }

        return empty($parts) ? null : implode("\n\n", $parts);
    }

    private function collectListItemsUntil(\DOMNode $start, ?\DOMNode $stop): array
    {
        $items = [];
        for ($node = $start->nextSibling; $node; $node = $node->nextSibling) {
            if ($stop && $this->nodeContainsOrIs($node, $stop)) {
                break;
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $name = Str::lower($node->nodeName);
            if (in_array($name, ['h1', 'h2', 'h3', 'h4', 'h5'], true)) {
                $heading = Str::lower($this->normalizeText($node->textContent));
                if (str_contains($heading, 'student achievement') || str_contains($heading, 'placement')) {
                    break;
                }
            }
            if (in_array($name, ['ul', 'ol'], true)) {
                foreach ($node->getElementsByTagName('li') as $li) {
                    $text = $this->normalizeText($li->textContent);
                    if ($text !== '') {
                        $items[] = ['description' => $text];
                    }
                }
            }
        }

        return $items;
    }

    private function collectStudentNarrative(\DOMXPath $xpath, \DOMNode $start, ?\DOMNode $stop): array
    {
        $items = [];
        $currentYear = null;
        $started = false;

        $nodes = $xpath->query('//*');
        foreach ($nodes as $node) {
            if (!$started) {
                if ($node->isSameNode($start)) {
                    $started = true;
                }
                continue;
            }
            if ($stop && $node->isSameNode($stop)) {
                break;
            }

            $name = Str::lower($node->nodeName);
            $own = $this->ownText($node);
            $ownLower = Str::lower($own);

            if (in_array($name, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) && $ownLower === 'placement') {
                break;
            }

            if (($name === 'p' || $name === 'strong') && str_contains($ownLower, 'appreciation list')) {
                if (preg_match('/(20\d{2}\s*[-–\/]\s*20?\d{2}|20\d{2})/', $own, $m)) {
                    $currentYear = preg_replace('/\s+/', '', $m[1]);
                }
                continue;
            }

            if (($name === 'strong' || $name === 'p' || in_array($name, ['h3', 'h4', 'h5'], true))
                && preg_match('/students?\s+achievements?\s*\(([^)]+)\)/i', $own, $m)
            ) {
                $currentYear = trim($m[1]);
                continue;
            }

            if ($name === 'li') {
                // Only direct-ish list items; skip nested menu noise by requiring parent weblist/ul after start
                $parent = $node->parentNode;
                if (!$parent || !in_array(Str::lower($parent->nodeName), ['ul', 'ol'], true)) {
                    continue;
                }
                $liText = $this->normalizeText($node->textContent);
                if ($liText === '' || strlen($liText) < 20) {
                    continue;
                }
                if (str_contains(Str::lower($liText), 'appreciation list')) {
                    continue;
                }
                $year = $currentYear;
                if (preg_match('/(20\d{2}\s*[-–\/]\s*20?\d{2}|20\d{2})/', $liText, $m)) {
                    $year = $year ?: preg_replace('/\s+/', '', $m[1]);
                }
                $items[] = [
                    'year' => $year ?: 'Other',
                    'title' => Str::limit($liText, 120, ''),
                    'description' => $liText,
                    'image' => null,
                    'image_url' => null,
                ];
            }

            if ($name === 'p') {
                $text = $this->normalizeText($node->textContent);
                $lower = Str::lower($text);
                if ($text === '' || strlen($text) < 40) {
                    continue;
                }
                if (str_contains($lower, 'appreciation list') || str_contains($lower, 'name of the student')) {
                    continue;
                }
                if (str_contains($lower, 'students achievements')) {
                    continue;
                }
                $year = $currentYear;
                if (preg_match('/(20\d{2}\s*[-–\/]\s*20?\d{2}|20\d{2})/', $text, $m)) {
                    $year = $year ?: preg_replace('/\s+/', '', $m[1]);
                }
                $items[] = [
                    'year' => $year ?: 'Other',
                    'title' => Str::limit($text, 100, '...'),
                    'description' => $text,
                    'image' => null,
                    'image_url' => null,
                ];
            }

            if (count($items) >= 300) {
                break;
            }
        }

        return $items;
    }

    private function parseAppreciationTables(\DOMXPath $xpath): array
    {
        $rows = [];
        $tables = $xpath->query('//table');

        foreach ($tables as $table) {
            $parsed = $this->parseTableRows($xpath, $table);
            $headerBlob = implode('|', $parsed['headers']);

            $isAppreciation = str_contains($headerBlob, 'appreciation')
                || (
                    str_contains($headerBlob, 'supervisor')
                    && str_contains($headerBlob, 'status')
                    && (str_contains($headerBlob, 'project') || str_contains($headerBlob, 'title of the project'))
                )
                || (
                    str_contains($headerBlob, 'event detail')
                    && str_contains($headerBlob, 'year')
                    && (str_contains($headerBlob, 'name'))
                );

            if (!$isAppreciation) {
                continue;
            }

            // Avoid research/phd tables
            if (str_contains($headerBlob, 'ph.d') || str_contains($headerBlob, 'thesis') || str_contains($headerBlob, 'registration')) {
                continue;
            }

            $year = null;
            $prevText = $this->normalizeText($this->previousMeaningfulText($table));
            if (preg_match('/(20\d{2}(?:\s*[-–\/]\s*(?:20)?\d{2})?)/', $prevText, $m)) {
                $year = preg_replace('/\s+/', '', $m[1]);
            }

            foreach ($parsed['rows'] as $row) {
                $map = $parsed['map'];
                $student = $this->cell($row, $map, ['name of the student', 'name', 'student']);
                $event = $this->cell($row, $map, ['event details', 'event detail', 'event name', 'event']);
                $yearOfStudent = $this->cell($row, $map, ['year of student', 'year']);
                // If "year" column is class year (II/III) keep; if it looks like academic year, use as group year
                if ($yearOfStudent !== '' && preg_match('/^20\d{2}/', $yearOfStudent)) {
                    $year = $year ?: $yearOfStudent;
                    $yearOfStudent = '';
                }
                $project = $this->cell($row, $map, ['title of the project', 'project name', 'project']);
                $supervisor = $this->cell($row, $map, ['supervisor']);
                $status = $this->cell($row, $map, ['appreciation /achievement', 'appreciation/achievement', 'appreciation', 'achievement', 'status']);

                // Section banner rows inside table (one cell spanning)
                $nonEmpty = array_values(array_filter($row, fn ($v) => trim((string) $v) !== ''));
                if (count($nonEmpty) === 1 && strlen($nonEmpty[0]) < 80 && !preg_match('/^\d/', $nonEmpty[0])) {
                    continue;
                }

                if ($student === '' && $event === '' && $project === '') {
                    continue;
                }

                $rows[] = [
                    'year' => $year ?: 'Other',
                    'student_name' => $student !== '' ? $student : '—',
                    'year_of_student' => $yearOfStudent !== '' ? $yearOfStudent : null,
                    'event_name' => $event !== '' ? $event : null,
                    'project_name' => $project !== '' ? $project : null,
                    'supervisor' => $supervisor !== '' ? $supervisor : null,
                    'result_status' => $status !== '' ? $status : null,
                ];
            }
        }

        return $rows;
    }

    private function parseStudentEventTables(\DOMXPath $xpath): array
    {
        $rows = [];
        $tables = $xpath->query('//table');

        foreach ($tables as $table) {
            $parsed = $this->parseTableRows($xpath, $table);
            $headers = $parsed['headers'];
            $headerBlob = implode('|', $headers);

            // Skip appreciation / research tables
            if (str_contains($headerBlob, 'appreciation')
                || str_contains($headerBlob, 'ph.d')
                || str_contains($headerBlob, 'thesis')
                || str_contains($headerBlob, 'supervisor') && str_contains($headerBlob, 'status') && str_contains($headerBlob, 'project')
            ) {
                continue;
            }

            $looksLikeContestTable = str_contains($headerBlob, 'contest')
                && str_contains($headerBlob, 'team')
                && (str_contains($headerBlob, 'participation') || str_contains($headerBlob, 'title'));

            $looksLikeEventTable = (
                (str_contains($headerBlob, 'student') || str_contains($headerBlob, 'name'))
                && (
                    str_contains($headerBlob, 'event nature')
                    || str_contains($headerBlob, 'organized')
                    || (str_contains($headerBlob, 'event') && str_contains($headerBlob, 'award'))
                    || (str_contains($headerBlob, 'nature') && str_contains($headerBlob, 'period'))
                )
            );

            $looksLikePrizeTable = str_contains($headerBlob, 'date of the event')
                && str_contains($headerBlob, 'name of the event')
                && (str_contains($headerBlob, 'prizes') || str_contains($headerBlob, 'students participated'));

            if (!$looksLikeEventTable && !$looksLikeContestTable && !$looksLikePrizeTable) {
                continue;
            }

            $year = null;
            $prevText = $this->normalizeText($this->previousMeaningfulText($table));
            if (preg_match('/(20\d{2}\s*[-–\/]\s*20?\d{2}|20\d{2})/', $prevText, $m)) {
                $year = preg_replace('/\s+/', '', $m[1]);
            }

            foreach ($parsed['rows'] as $row) {
                $map = $parsed['map'];
                if ($looksLikeContestTable) {
                    $student = $this->cell($row, $map, ['team members', 'team']);
                    $eventNature = $this->cell($row, $map, ['contest name', 'contest']);
                    $eventName = $this->cell($row, $map, ['title']);
                    $period = null;
                    $organized = null;
                    $awards = $this->cell($row, $map, ['participation', 'award', 'awards']);
                    $yearOf = $this->cell($row, $map, ['year']);
                    $rowYear = $year ?: ($yearOf !== '' ? $yearOf : 'Other');
                } elseif ($looksLikePrizeTable) {
                    $student = $this->cell($row, $map, ['name of the students participated', 'students participated', 'student']);
                    $eventNature = null;
                    $eventName = $this->cell($row, $map, ['name of the event', 'event']);
                    $period = $this->cell($row, $map, ['date of the event', 'date']);
                    $organized = $this->cell($row, $map, ['conducted by', 'organized by', 'organized']);
                    $awards = $this->cell($row, $map, ['prizes won', 'prizes', 'award', 'awards']);
                    $rowYear = $year ?: 'Other';
                    if ($period !== '' && preg_match('/(20\d{2})/', $period, $m)) {
                        $rowYear = $year ?: $m[1];
                    }
                } else {
                    $student = $this->cell($row, $map, ['name of the student', 'student name', 'name', 'student']);
                    $eventNature = $this->cell($row, $map, ['event nature', 'nature of the event', 'nature']);
                    $eventName = $this->cell($row, $map, ['event name', 'title of the event', 'title', 'event']);
                    $period = $this->cell($row, $map, ['period/date', 'period / date', 'period', 'date']);
                    $organized = $this->cell($row, $map, ['organized by', 'organized']);
                    $awards = $this->cell($row, $map, ['awards', 'award']);
                    $rowYear = $year ?: 'Other';
                }

                if ($student === '' && $eventName === '') {
                    continue;
                }

                $rows[] = [
                    'year' => $rowYear,
                    'student_name' => $student !== '' ? $student : '—',
                    'event_nature' => !empty($eventNature) ? $eventNature : null,
                    'event_name' => $eventName !== '' ? $eventName : null,
                    'period_date' => !empty($period) ? $period : null,
                    'organized_by' => !empty($organized) ? $organized : null,
                    'awards' => !empty($awards) ? $awards : null,
                ];
            }
        }

        return $rows;
    }

    private function parseTableRows(\DOMXPath $xpath, \DOMNode $table): array
    {
        $headers = [];
        $firstRow = $xpath->query('./thead/tr[1]|./tbody/tr[1]|./tr[1]', $table)->item(0);
        if ($firstRow) {
            foreach ($xpath->query('./th|./td', $firstRow) as $cell) {
                $headers[] = Str::lower($this->normalizeText($cell->textContent));
            }
        }

        $map = [];
        foreach ($headers as $i => $header) {
            if ($header !== '') {
                $map[$header] = $i;
            }
        }

        $bodyRows = $xpath->query('./thead/tr|./tbody/tr|./tr', $table);
        $rows = [];
        $skippedHeader = false;
        foreach ($bodyRows as $tr) {
            $cells = [];
            foreach ($xpath->query('./th|./td', $tr) as $cell) {
                $cells[] = $this->normalizeText($cell->textContent);
            }
            if (empty($cells)) {
                continue;
            }
            if (!$skippedHeader) {
                $joined = Str::lower(implode('|', $cells));
                if (
                    str_contains($joined, 's.no')
                    || str_contains($joined, 'name')
                    || str_contains($joined, 'contest')
                    || str_contains($joined, 'date of the event')
                    || $joined === implode('|', $headers)
                ) {
                    $skippedHeader = true;
                    continue;
                }
                $skippedHeader = true;
            }
            $rows[] = $cells;
        }

        return [
            'headers' => $headers,
            'map' => $map,
            'rows' => $rows,
        ];
    }

    private function cell(array $row, array $map, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            foreach ($map as $header => $index) {
                if ($header === $candidate || str_contains($header, $candidate)) {
                    return trim((string) ($row[$index] ?? ''));
                }
            }
        }

        return '';
    }

    private function findNextTable(\DOMNode $node): ?\DOMNode
    {
        for ($n = $node->nextSibling; $n; $n = $n->nextSibling) {
            if ($n->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            if (Str::lower($n->nodeName) === 'table') {
                return $n;
            }
            if ($n->hasChildNodes()) {
                $tables = $n->getElementsByTagName('table');
                if ($tables->length > 0) {
                    return $tables->item(0);
                }
            }
            $name = Str::lower($n->nodeName);
            if (in_array($name, ['h1', 'h2', 'h3', 'h4', 'h5'], true)) {
                break;
            }
        }

        // Also search parent siblings for nearby table (WP wrapping)
        $parent = $node->parentNode;
        if ($parent) {
            for ($n = $parent->nextSibling; $n; $n = $n->nextSibling) {
                if ($n->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                if (Str::lower($n->nodeName) === 'table') {
                    return $n;
                }
                if ($n->hasChildNodes()) {
                    $tables = $n->getElementsByTagName('table');
                    if ($tables->length > 0) {
                        return $tables->item(0);
                    }
                }
                break;
            }
        }

        return null;
    }

    private function previousMeaningfulText(\DOMNode $node): string
    {
        for ($n = $node->previousSibling; $n; $n = $n->previousSibling) {
            if ($n->nodeType === XML_ELEMENT_NODE) {
                $text = $this->normalizeText($n->textContent);
                if ($text !== '') {
                    return $text;
                }
            }
        }
        $parent = $node->parentNode;
        if ($parent) {
            for ($n = $parent->previousSibling; $n; $n = $n->previousSibling) {
                if ($n->nodeType === XML_ELEMENT_NODE) {
                    $text = $this->normalizeText($n->textContent);
                    if ($text !== '') {
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    private function nodeContainsOrIs(\DOMNode $haystack, \DOMNode $needle): bool
    {
        if ($haystack->isSameNode($needle)) {
            return true;
        }
        if (!$haystack->hasChildNodes()) {
            return false;
        }
        foreach ($haystack->childNodes as $child) {
            if ($child->isSameNode($needle) || $this->nodeContainsOrIs($child, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(?string $text): string
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        return trim($text);
    }

    private function downloadImage(string $url, int $departmentId, string $label): ?string
    {
        $url = str_replace('http://psr.edu.in', 'https://psr.edu.in', $url);
        try {
            $response = Http::timeout(60)
                ->withHeaders(['User-Agent' => 'PSRCollegeAchievementsSync/1.0'])
                ->get($url);
            if (!$response->successful()) {
                return null;
            }
            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            $ext = Str::lower(preg_replace('/[^a-z0-9]/i', '', $ext) ?: 'jpg');
            $filename = 'dept' . $departmentId . '_' . Str::slug(Str::limit($label, 40, '')) . '_' . time() . '_' . Str::random(4) . '.' . $ext;
            $dir = public_path('uploads/achievement-items/');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0777, true, true);
            }
            File::put($dir . $filename, $response->body());
            $rootDir = base_path('uploads/achievement-items/');
            if (!File::exists($rootDir)) {
                File::makeDirectory($rootDir, 0777, true, true);
            }
            File::copy($dir . $filename, $rootDir . $filename);
            return $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
