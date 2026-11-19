<?php
namespace App\Http\Controllers;
use App\Models\Menu;
use Illuminate\Http\Request;
class MenuController extends Controller
{
    /**
     * Menu labels that should open an external URL in a new tab.
     */
    private array $externalMenuLinks = [
        'NPTEL' => 'https://nptel.ac.in/localchapter/statistics/587',
        'Automation System' => 'https://psr.ibossems.com/',
        // Local PDFs — resolved via asset() so they follow APP_URL / current host
        'Autonomous Undertaking' => 'uploads/pdf/wp-content/uploads/2020/09/Autonomous-Undertaking.pdf',
        'Mandatory Disclosure' => 'uploads/pdf/wp-content/uploads/2025/07/Mandatory-Disclosures-2025-26.pdf',
        'Internal Complaints Committee' => 'uploads/pdf/wp-content/uploads/2026/03/Internal-Complaints-Committee.pdf',
        'Reservation(SC/ST/OBC) and Minority Cell' => 'uploads/pdf/wp-content/uploads/2026/03/Reservation-Cell.pdf',
        'Anti-Drugs Club / Committee' => 'uploads/pdf/wp-content/uploads/2026/03/4965_PSR_EC_AntiDrugClubReport.pdf',
        'PSR in Media' => 'https://photos.google.com/share/AF1QipNd2xu-hPSbeHCqkHIg-llWrma9jyI6VZpAfH0PjH2M9Unc67TzSd1VkkPtaAfeVA?key=b1BJeWxuTUV1WnMwcmEwNGpuOTdxYjE5aUpBX2tn',
        // Static landing pages (served from project-root folders, same as old WordPress)
        'ICOACT-2025' => '/icoact/',
        'ICOACT-2027' => '/icoact/',
        'ICoACT-2025' => '/icoact/',
        'ICoACT-2027' => '/icoact/',
    ];

    /**
     * Regulation year PDFs under Academics > Regulations > UG|PG.
     * Paths are relative to public root and resolved via asset().
     */
    private array $regulationPdfLinks = [
        'UG' => [
            '2023' => 'uploads/pdf/wp-content/uploads/2026/06/Regulation-2023-UG.pdf',
            '2019 Amendments' => 'uploads/pdf/wp-content/uploads/2023/01/R2019-UG.pdf',
            '2019' => 'uploads/pdf/wp-content/uploads/2021/12/regulation-2019-ug.pdf',
            '2016' => 'uploads/pdf/UG-Regulation-2016.pdf',
            '2012' => 'uploads/pdf/UG-Regulation-2012.pdf',
        ],
        'PG' => [
            '2023' => 'uploads/pdf/wp-content/uploads/2024/04/Regulation-2023-PG.pdf',
            '2019 Amendments' => 'uploads/pdf/wp-content/uploads/2023/01/R2019PG.pdf',
            '2019' => 'uploads/pdf/wp-content/uploads/2021/12/regulation-2019-pg.pdf',
            '2016' => 'uploads/pdf/PG-Regulation-2016.pdf',
            '2012' => 'uploads/pdf/PG-Regulation-2012.pdf',
        ],
    ];

    public function index()
    {
        try {
            $menus = Menu::query()
                ->where(function ($q) {
                    $q->where('location', 'header')->orWhereNull('location');
                })
                ->where('status', 0)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $treeView = $this->buildTreeView($menus);
           // dd($treeView);
            return view('web.layouts.menu', ['treeView' => $treeView]);
        } catch (\Exception $e) {
            // Log or display the error message
           // dd($e->getMessage());
        }
    }
    public function generateBreadcrumb($pageTitle, $pageSlug = null)
    {
        $breadcrumbItems = [];
        
        // Try multiple matching strategies
        $menuItem = null;
        
        // 1. Exact match by title
        $menuItem = Menu::where('menu', $pageTitle)->first();
        
        // 2. If no exact match, try by slug
        if (!$menuItem && $pageSlug) {
            $menuItem = Menu::where('menu', 'LIKE', '%' . $pageTitle . '%')->first();
        }
        
        // 3. If still no match, try to convert slug to menu name
        if (!$menuItem && $pageSlug) {
            $menuName = ucwords(str_replace(['-', '_'], ' ', $pageSlug));
            $menuItem = Menu::where('menu', $menuName)->first();
        }
        
        if ($menuItem) {
            // Build breadcrumb path from root to current item
            $currentItem = $menuItem;
            $path = [];
            
            // Traverse up the hierarchy
            while ($currentItem) {
                array_unshift($path, $currentItem);
                $currentItem = $currentItem->parent_id ? Menu::find($currentItem->parent_id) : null;
            }
            
            // Generate breadcrumb items
            foreach ($path as $index => $item) {
                if ($index === count($path) - 1) {
                    // Last item (current page)
                    $breadcrumbItems[] = ['title' => $item->menu, 'url' => null, 'active' => true];
                } else {
                    // Generate URL for parent items
                    $link = $this->resolveMenuLink($item);
                    $breadcrumbItems[] = ['title' => $item->menu, 'url' => $link['url'], 'active' => false];
                }
            }
        } else {
            // Fallback: Home -> Current Page
            $breadcrumbItems = [
                ['title' => 'Home', 'url' => url('/'), 'active' => false],
                ['title' => $pageTitle, 'url' => null, 'active' => true]
            ];
        }
        
        return $breadcrumbItems;
    }

    private function isDepartmentProgram($menuName)
    {
        // Check if this is a department program by looking for department names
        $departmentPrograms = [
            'Artificial Intelligence & Data Science (AI & DS)',
            'Bio Medical(BM)',
            'Bio-Technology (BT)',
            'Civil Engineering (CIVIL)',
            'Computer Science and Engineering (CSE)',
            'Electrical and Electronics Engineering (EEE)',
            'Electronics & Communication Engineering (ECE)',
            'Information Technology (IT)',
            'Mechanical Engineering (MECH)',
            'Science and Humanities (S&H)',
            'Applied Electronics (AE)',
            'Engineering Design (ED)',
            'Master of Business Administration (MBA)',
            'Power Electronics and Drives (PED)',
            'Structural Engineering (SE)'
        ];
        
        return in_array($menuName, $departmentPrograms);
    }

    private function getDepartmentSlug($menuName)
    {
        // Map menu names to department slugs
        $departmentSlugMap = [
            'Artificial Intelligence & Data Science (AI & DS)' => 'artificial-intelligence-data-science',
            'Bio Medical(BM)' => 'biomedical-engineering',
            'Bio-Technology (BT)' => 'biotechnology',
            'Civil Engineering (CIVIL)' => 'civil-engineering',
            'Computer Science and Engineering (CSE)' => 'computer-science-engineering',
            'Electrical and Electronics Engineering (EEE)' => 'electrical-electronics-engineering',
            'Electronics & Communication Engineering (ECE)' => 'electronics-communication-engineering',
            'Information Technology (IT)' => 'information-technology',
            'Mechanical Engineering (MECH)' => 'mechanical-engineering',
            'Science and Humanities (S&H)' => 'science-humanities',
            'Applied Electronics (AE)' => 'applied-electronics',
            'Engineering Design (ED)' => 'engineering-design',
            'Master of Business Administration (MBA)' => 'master-of-business-administration',
            'Power Electronics and Drives (PED)' => 'power-electronics-drives',
            'Structural Engineering (SE)' => 'structural-engineering'
        ];
        
        return $departmentSlugMap[$menuName] ?? strtolower(str_replace([' ', '&', '/', '(', ')'], ['-', 'and', '-', '', ''], $menuName));
    }

    /**
     * Resolve menu URL and whether it should open externally.
     *
     * @return array{url:string,external:bool}
     */
    public function resolveMenuLink($menu, $menus = null): array
    {
        $forceNewTab = !empty($menu->open_in_new_tab);
        $linkType = $menu->link_type ?? 'legacy';

        if ($linkType && $linkType !== 'legacy') {
            $resolved = $this->resolveTypedMenuLink($menu);
            if ($forceNewTab) {
                $resolved['external'] = true;
            }

            return $resolved;
        }

        $name = $menu->menu;

        if ($name === 'Home') {
            return ['url' => url('/'), 'external' => $forceNewTab];
        }

        if (isset($this->externalMenuLinks[$name])) {
            return [
                'url' => $this->toAppUrl($this->externalMenuLinks[$name]),
                'external' => true,
            ];
        }

        $parentName = null;
        if ($menus) {
            $parent = $menus->firstWhere('id', $menu->parent_id);
            $parentName = $parent->menu ?? null;
        } elseif ($menu->parent_id) {
            $parent = Menu::find($menu->parent_id);
            $parentName = $parent->menu ?? null;
        }

        if ($parentName && isset($this->regulationPdfLinks[$parentName][$name])) {
            return [
                'url' => $this->toAppUrl($this->regulationPdfLinks[$parentName][$name]),
                'external' => true,
            ];
        }

        if ($this->isDepartmentProgram($name)) {
            return [
                'url' => url('/department/' . $this->getDepartmentSlug($name)),
                'external' => $forceNewTab,
            ];
        }

        $slug = strtolower(str_replace([' ', '&', '/'], ['-', 'and', '-'], $name));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = trim($slug, '-');

        $pageExists = \App\Models\Web\Page::where('slug', $slug)->where('status', 1)->exists();
        if ($pageExists) {
            return ['url' => url('/' . $slug), 'external' => $forceNewTab];
        }

        return ['url' => '#', 'external' => $forceNewTab];
    }

    /**
     * @return array{url:string,external:bool}
     */
    private function resolveTypedMenuLink($menu): array
    {
        $type = $menu->link_type ?? 'none';
        $value = trim((string) ($menu->link_value ?? ''));
        $section = trim((string) ($menu->link_section ?? ''));

        switch ($type) {
            case 'none':
                return ['url' => '#', 'external' => false];

            case 'page':
                if ($value === '') {
                    return ['url' => '#', 'external' => false];
                }

                return ['url' => url('/' . ltrim($value, '/')), 'external' => false];

            case 'department':
                if ($value === '') {
                    return ['url' => '#', 'external' => false];
                }
                $path = 'department/' . ltrim($value, '/');
                if ($section !== '') {
                    $path .= '/' . ltrim($section, '/');
                }

                return ['url' => url($path), 'external' => false];

            case 'custom':
                if ($value === '') {
                    return ['url' => '#', 'external' => false];
                }
                if (preg_match('#^https?://#i', $value)) {
                    return ['url' => $value, 'external' => true];
                }
                if (str_starts_with($value, '//')) {
                    return ['url' => 'https:' . $value, 'external' => true];
                }
                // Site-relative or uploads path
                return [
                    'url' => url(ltrim($value, '/')),
                    'external' => (bool) preg_match('/\.pdf($|\?)/i', $value),
                ];

            default:
                return ['url' => '#', 'external' => false];
        }
    }

    public function buildTreeView($menus, $parent = 0, $level = 0, $prelevel = -1)
    {
        $html = '';
        $children = $menus->where('parent_id', $parent)->sortBy([
            ['sort_order', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($children as $menu) {
            if ($level > $prelevel) {
                $html .= ($html == '') ? '<ul class="" id="">' : '<ul class="" id="">';
            }
            if ($level == $prelevel) {
                $html .= '</li>';
            }
            if ($level > $prelevel) {
                $prelevel = $level;
            }

            $hasChildren = $menus->where('parent_id', $menu->id)->count() > 0;
            $menu_arrow = $hasChildren ? '&nbsp;<span class="caret"></span>' : '';

            $link = $this->resolveMenuLink($menu, $menus);
            $url = htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8');
            $attrs = 'href="' . $url . '"';
            if ($link['external']) {
                $attrs .= ' target="_blank" rel="noopener noreferrer"';
            }

            $html .= '<li class=""><a ' . $attrs . '>' . e($menu->menu) . $menu_arrow . '</a>';
            $level++;
            $html .= $this->buildTreeView($menus, $menu->id, $level, $prelevel);
            $level--;
        }
        if ($level == $prelevel) {
            $html .= '</li></ul>';
        }

        return $html;
    }

    /**
     * Convert stored link values to host-relative app URLs.
     * Absolute http(s) URLs are left unchanged (external sites only).
     */
    private function toAppUrl(string $link): string
    {
        if ($link === '' || $link === '#') {
            return $link;
        }

        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        if (str_starts_with($link, '/')) {
            return url(ltrim($link, '/'));
        }

        return asset($link);
    }
}
