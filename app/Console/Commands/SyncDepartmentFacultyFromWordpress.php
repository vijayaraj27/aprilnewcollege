<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Language;
use App\Models\Web\FacultyMember;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncDepartmentFacultyFromWordpress extends Command
{
    protected $signature = 'faculty:sync-from-wordpress
                            {--department= : Local department id to sync}
                            {--dry-run : Parse and report without writing}
                            {--skip-photos : Do not download photo files}';

    protected $description = 'Sync teaching/non-teaching faculty from psr.edu.in (WP page discovery + HTML table parse)';

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
        $skipPhotos = (bool) $this->option('skip-photos');
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

        $uploadPath = public_path('uploads/faculty-members/');
        if (!$dryRun && !File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $totalTeaching = 0;
        $totalNonTeaching = 0;
        $totalPhotos = 0;
        $failed = 0;

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
                    $failed++;
                    continue;
                }

                $pageUrl = $page['link'] ?? ($this->baseUrl . '/ug-programs/' . $wpSlug . '/');
                $pageUrl = str_replace('http://psr.edu.in', 'https://psr.edu.in', $pageUrl);

                $html = $this->fetchHtml($pageUrl);
                if ($html === null) {
                    $this->error("  Failed to fetch HTML: {$pageUrl}");
                    $failed++;
                    continue;
                }

                [$teaching, $nonTeaching] = $this->parseFacultyTables($html);

                if (!$skipPhotos && !$dryRun) {
                    foreach ($teaching as &$row) {
                        if (!empty($row['photo_url'])) {
                            $saved = $this->downloadPhoto($row['photo_url'], $department->id, $row['name'] ?? 'faculty');
                            if ($saved) {
                                $row['photo'] = $saved;
                                $totalPhotos++;
                            }
                        }
                    }
                    unset($row);
                }

                $this->line('  Teaching: ' . count($teaching) . ' | Non-teaching: ' . count($nonTeaching));

                if ($dryRun) {
                    $totalTeaching += count($teaching);
                    $totalNonTeaching += count($nonTeaching);
                    continue;
                }

                if (count($teaching) === 0 && count($nonTeaching) === 0) {
                    $this->warn('  No faculty tables parsed; keeping existing records.');
                    continue;
                }

                if (count($teaching) > 0) {
                    FacultyMember::where('department_id', $department->id)->where('type', 'teaching')->delete();
                    foreach ($teaching as $i => $row) {
                        FacultyMember::create([
                            'language_id' => $languageId,
                            'department_id' => $department->id,
                            'type' => 'teaching',
                            'name' => $row['name'],
                            'designation' => $row['designation'] ?? null,
                            'email' => $row['email'] ?? null,
                            'au_fin' => $row['au_fin'] ?? null,
                            'aicte_id' => $row['aicte_id'] ?? null,
                            'profile_url' => $row['profile_url'] ?? null,
                            'photo' => $row['photo'] ?? null,
                            'sort_order' => $i + 1,
                            'status' => 1,
                        ]);
                    }
                }

                if (count($nonTeaching) > 0) {
                    FacultyMember::where('department_id', $department->id)->where('type', 'non-teaching')->delete();
                    foreach ($nonTeaching as $i => $row) {
                        FacultyMember::create([
                            'language_id' => $languageId,
                            'department_id' => $department->id,
                            'type' => 'non-teaching',
                            'name' => $row['name'],
                            'qualification' => $row['qualification'] ?? null,
                            'designation' => $row['designation'] ?? null,
                            'sort_order' => $i + 1,
                            'status' => 1,
                        ]);
                    }
                }

                $totalTeaching += count($teaching);
                $totalNonTeaching += count($nonTeaching);
            } catch (\Throwable $e) {
                $failed++;
                $this->error('  Error: ' . $e->getMessage());
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Teaching rows', $totalTeaching],
                ['Non-teaching rows', $totalNonTeaching],
                ['Photos downloaded', $totalPhotos],
                ['Failed departments', $failed],
                ['Mode', $dryRun ? 'dry-run' : 'write'],
            ]
        );

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function fetchWpPage(string $slug): ?array
    {
        $response = Http::timeout(60)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'PSRCollegeFacultySync/1.0'])
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
            ->withHeaders(['User-Agent' => 'PSRCollegeFacultySync/1.0'])
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * @return array{0: array<int,array>, 1: array<int,array>}
     */
    private function parseFacultyTables(string $html): array
    {
        $teaching = [];
        $nonTeaching = [];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table');

        $teachingCandidates = [];
        $nonTeachingCandidates = [];

        foreach ($tables as $table) {
            $headers = [];
            $firstRow = $xpath->query('./thead/tr[1]|./tr[1]', $table)->item(0);
            if (!$firstRow) {
                continue;
            }
            $headerCells = $xpath->query('./th|./td', $firstRow);
            foreach ($headerCells as $cell) {
                $headers[] = Str::lower(trim(preg_replace('/\s+/', ' ', $cell->textContent)));
            }

            if (empty($headers)) {
                continue;
            }

            $headerBlob = implode('|', $headers);

            if ($this->looksLikeTeachingHeader($headerBlob)) {
                $rows = $this->parseTeachingRows($xpath, $table, $headers);
                if (!empty($rows)) {
                    $teachingCandidates[] = $rows;
                }
                continue;
            }

            if ($this->looksLikeNonTeachingHeader($headerBlob)) {
                $rows = $this->parseNonTeachingRows($xpath, $table, $headers);
                if (!empty($rows)) {
                    $nonTeachingCandidates[] = $rows;
                }
            }
        }

        // Prefer the richest single table (avoid merging duplicate/other dept tables)
        $teaching = $this->pickLargestCandidate($teachingCandidates);
        $nonTeaching = $this->pickLargestCandidate($nonTeachingCandidates);

        return [$teaching, $nonTeaching];
    }

    private function pickLargestCandidate(array $candidates): array
    {
        if (empty($candidates)) {
            return [];
        }

        usort($candidates, function ($a, $b) {
            return count($b) <=> count($a);
        });

        return $candidates[0];
    }

    private function looksLikeTeachingHeader(string $headerBlob): bool
    {
        $hasPerson = str_contains($headerBlob, 'faculty') || str_contains($headerBlob, 'name');
        $hasEmail = str_contains($headerBlob, 'mail');
        $hasIds = str_contains($headerBlob, 'aicte')
            || str_contains($headerBlob, 'au - fin')
            || str_contains($headerBlob, 'au-fin')
            || str_contains($headerBlob, 'au fin');

        return $hasPerson && $hasEmail && $hasIds;
    }

    private function looksLikeNonTeachingHeader(string $headerBlob): bool
    {
        return str_contains($headerBlob, 'qualification')
            && str_contains($headerBlob, 'designation')
            && (str_contains($headerBlob, 'name') || str_contains($headerBlob, 'staff'))
            && !str_contains($headerBlob, 'aicte')
            && !str_contains($headerBlob, 'au - fin')
            && !str_contains($headerBlob, 'au-fin');
    }

    private function parseTeachingRows(\DOMXPath $xpath, \DOMElement $table, array $headers): array
    {
        $map = $this->mapHeaders($headers, [
            'faculty' => ['faculty', 'name', 'faculty name'],
            'email' => ['e-mail', 'email', 'mail'],
            'au_fin' => ['au - fin', 'au-fin', 'au fin', 'aufin'],
            'aicte_id' => ['aicte id', 'aicte', 'ai cte id'],
            'photo' => ['photo'],
            'profile' => ['profile'],
        ]);

        $rows = [];
        $trList = $xpath->query('.//tr', $table);
        foreach ($trList as $index => $tr) {
            if ($index === 0) {
                continue;
            }
            $cells = $xpath->query('./th|./td', $tr);
            if ($cells->length === 0) {
                continue;
            }

            $values = [];
            foreach ($cells as $cell) {
                $values[] = $cell;
            }

            // Skip repeated header rows inside the table body
            $firstCell = Str::lower($this->cellText($values, 0));
            if (in_array($firstCell, ['faculty', 'name', 'faculty name'], true)) {
                continue;
            }

            $facultyRaw = $this->cellText($values, $map['faculty'] ?? null);
            if ($facultyRaw === '') {
                continue;
            }

            [$name, $designation] = $this->splitNameDesignation($facultyRaw);
            $email = $this->cellText($values, $map['email'] ?? null);
            $auFin = $this->cellText($values, $map['au_fin'] ?? null);
            $aicte = $this->cellText($values, $map['aicte_id'] ?? null);
            $photoUrl = $this->cellImageUrl($values, $map['photo'] ?? null);
            $profileUrl = $this->cellLinkUrl($values, $map['profile'] ?? null);

            // Skip obvious non-data / empty photo-only rows
            if ($name === '' || Str::lower($name) === 'faculty') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'designation' => $designation,
                'email' => $email !== '' ? $email : null,
                'au_fin' => $auFin !== '' ? $auFin : null,
                'aicte_id' => $aicte !== '' ? $aicte : null,
                'photo_url' => $photoUrl,
                'profile_url' => $profileUrl,
            ];
        }

        return $rows;
    }

    private function parseNonTeachingRows(\DOMXPath $xpath, \DOMElement $table, array $headers): array
    {
        $map = $this->mapHeaders($headers, [
            'name' => ['name', 'faculty', 'staff'],
            'qualification' => ['qualification'],
            'designation' => ['designation'],
        ]);

        $rows = [];
        $trList = $xpath->query('.//tr', $table);
        foreach ($trList as $index => $tr) {
            if ($index === 0) {
                continue;
            }
            $cells = $xpath->query('./th|./td', $tr);
            if ($cells->length === 0) {
                continue;
            }
            $values = [];
            foreach ($cells as $cell) {
                $values[] = $cell;
            }

            $name = $this->cellText($values, $map['name'] ?? 0);
            if ($name === '' || Str::lower($name) === 'name') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'qualification' => $this->nullableText($this->cellText($values, $map['qualification'] ?? null)),
                'designation' => $this->nullableText($this->cellText($values, $map['designation'] ?? null)),
            ];
        }

        return $rows;
    }

    private function mapHeaders(array $headers, array $aliases): array
    {
        $map = [];
        foreach ($aliases as $key => $names) {
            foreach ($headers as $i => $header) {
                foreach ($names as $name) {
                    if ($header === $name || str_contains($header, $name)) {
                        $map[$key] = $i;
                        break 2;
                    }
                }
            }
        }
        return $map;
    }

    private function cellText(array $cells, $index): string
    {
        if ($index === null || !isset($cells[$index])) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $cells[$index]->textContent ?? ''));
    }

    private function cellImageUrl(array $cells, $index): ?string
    {
        if ($index === null || !isset($cells[$index])) {
            return null;
        }
        $imgs = $cells[$index]->getElementsByTagName('img');
        if ($imgs->length === 0) {
            return null;
        }

        $img = $imgs->item(0);
        $candidates = [
            $img->getAttribute('data-src'),
            $img->getAttribute('data-lazy-src'),
            $img->getAttribute('data-original'),
            $img->getAttribute('data-large_image'),
            $img->getAttribute('src'),
        ];

        // srcset may hold the real URL when src is a tiny placeholder
        $srcset = $img->getAttribute('data-srcset') ?: $img->getAttribute('srcset');
        if ($srcset !== '') {
            $parts = array_map('trim', explode(',', $srcset));
            if (!empty($parts[0])) {
                $first = preg_split('/\s+/', $parts[0]);
                $candidates[] = $first[0] ?? '';
            }
        }

        $src = null;
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '' || str_starts_with($candidate, 'data:')) {
                continue;
            }
            $src = $candidate;
            break;
        }

        if ($src === null) {
            return null;
        }
        if (str_starts_with($src, '//')) {
            $src = 'https:' . $src;
        } elseif (str_starts_with($src, '/')) {
            $src = $this->baseUrl . $src;
        }
        return str_replace('http://psr.edu.in', 'https://psr.edu.in', $src);
    }

    private function downloadPhoto(string $url, $departmentId, string $name): ?string
    {
        if (str_starts_with($url, 'data:')) {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders(['User-Agent' => 'PSRCollegeFacultySync/1.0'])
                ->get($url);
            if (!$response->successful()) {
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext)) ?: 'jpg';
            $filename = 'dept' . $departmentId . '_' . Str::slug(Str::limit($name, 40, '')) . '_' . time() . '_' . Str::random(4) . '.' . $ext;
            $path = public_path('uploads/faculty-members/' . $filename);
            File::put($path, $response->body());
            return $filename;
        } catch (\Throwable $e) {
            $this->warn('  Photo download failed: ' . $e->getMessage());
            return null;
        }
    }

    private function cellLinkUrl(array $cells, $index): ?string
    {
        if ($index === null || !isset($cells[$index])) {
            return null;
        }
        $anchors = $cells[$index]->getElementsByTagName('a');
        if ($anchors->length === 0) {
            return null;
        }
        $href = trim($anchors->item(0)->getAttribute('href'));
        if ($href === '' || $href === '#') {
            return null;
        }
        if (str_starts_with($href, '/')) {
            $href = $this->baseUrl . $href;
        }
        return str_replace('http://psr.edu.in', 'https://psr.edu.in', $href);
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function splitNameDesignation(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));

        // Common pattern: "Dr.P.MARICHAMY Professor & Dean"
        if (preg_match('/^(.*?)\s+(Professor.*?|Associate Professor.*?|Assistant Professor.*?|Lab .+|HOD.*?|Dean.*?)$/i', $raw, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$raw, null];
    }

    private function nullableText(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
