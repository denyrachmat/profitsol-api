<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\CMS\FormController;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMasterTitle;
use App\Models\DMS\DMSShareDet;
use Illuminate\Http\Request;
use App\Traits\PORTAL\GencodeTraits;
use App\Models\PORTAL\PortalGencode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\PORTAl\notifSentQueue;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FrontPageController extends BaseController
{
    use GencodeTraits;

    protected $headerImage = null;

    private function normalizeIntId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === '#') {
                return null;
            }

            if (!is_numeric($trimmed)) {
                return null;
            }

            $value = $trimmed;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    private function clearNavMenuCache(): void
    {
        Cache::forget('portal.nav_menu.default');
        Cache::forget('portal.nav_menu.all');
    }

    function __construct()
    {
        $this->headerImage = null;
    }
    public function getFPMenu($id = '', Request $request = null)
    {
        if (empty($id)) {
            $data = $this->getDataGencode('FP_CONF_MENU', [], [
                'idx' => 'id',
                'value' => 'pgm_value',
                'label' => 'pgm_desc',
                'icon' => 'pgm_value2',
                'index' => 'pgm_value3',
                'isFrontData' => 'pgm_desc2',
            ]);
        } else {
            $data = $this->getDataGencode('FP_CONF_MENU', ['id' => $id], [
                'idx' => 'id',
                'value' => 'pgm_value',
                'label' => 'pgm_desc',
                'icon' => 'pgm_value2',
                'index' => 'pgm_value3',
                'isFrontData' => 'pgm_desc2',
            ]);
        }

        foreach ($data as $key => $value) {
            $dataMapFP = $this->getDataGencode(
                'FP_CONF_MENU_VIEW',
                [
                    'pgm_value' => !empty($request) ? $request->header('username', '') : '',
                    'pgm_value2' => $value['idx']
                ],
                [
                    'idx' => 'id',
                    'value' => 'pgm_value3',
                    'label' => 'pgm_desc',
                    'icon' => 'pgm_value2',
                ],
                [],
                true
            );
            $data[$key]['view_option'] = $dataMapFP['value'] ?? (
                $value['isFrontData'] == 1 ? 'all' : 'own'
            );
        }

        usort($data, function ($a, $b) {
            return ($a['index'] ?? 0) <=> ($b['index'] ?? 0);
        });

        if (empty($data)) {
            return $this->handleError('No front page menu found', 404);
        } else {
            return $this->handleResponse($data, 'Front page menu retrieved successfully');
        }
    }

    public function getNavMenuFromAPI($showAll = false)
    {
        $cacheKey = 'portal.nav_menu.' . ($showAll ? 'all' : 'default');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($showAll) {
            return $this->getNavMenu([], (bool) $showAll);
        });
    }

    public function getNavMenu($data = [], $showAll = false, $id = '')
    {
        if (empty($data)) {
            $data = $this->getDataGencode('FP_NAV', !empty($id) ? ['id' => $id] : [], [
                'idx' => 'id',
                'value' => 'id',
                'label' => 'pgm_value',
                'icon' => 'pgm_value2',
                'type' => 'pgm_desc',
                'linkto' => 'pgm_value3',
                'page' => 'pgm_value3',
                'url' => 'pgm_value3',
                'children' => 'children',
                'parent' => 'pgm_parent',
                'tags' => 'pgm_desc2',
                'dmsShared' => 'pgm_desc3|bool',
                'order' => 'pgm_order',
            ], [
                'pgm_order' => 'asc',
                'id' => 'asc'
            ], false, true, $showAll);
        }

        $pages = $this->getDataGencode('URL_PAGE_GEN', [], [
            'value' => 'pgm_value',
            'url' => 'pgm_desc',
            'is_main' => 'pgm_value2',
            'desc' => 'pgm_desc2',
        ]);

        $pagesByValue = [];
        foreach ($pages as $page) {
            if (isset($page['value'])) {
                $pagesByValue[(string) $page['value']] = $page;
            }
        }

        $postsByTagsCache = [];

        $processTree = function ($items) use (&$processTree, $pagesByValue, &$postsByTagsCache) {
            foreach ($items as &$navItem) {
                $pageData = $pagesByValue[(string) ($navItem['linkto'] ?? '')] ?? [];

                $navItem['is_main'] = $pageData['is_main'] ?? 0;
                $navItem['pages'] = $pageData;
                $navItem['url'] = isset($pageData['url']) ? (string) $pageData['url'] : '';
                $navItem['forms'] = [];

                if (isset($navItem['children']) && is_array($navItem['children']) && count($navItem['children']) > 0) {
                    $navItem['children'] = $processTree($navItem['children']);
                }

                if (!empty($navItem['tags'])) {
                    $decodedTags = is_array($navItem['tags'])
                        ? $navItem['tags']
                        : json_decode((string) $navItem['tags'], true);

                    if (is_array($decodedTags) && count($decodedTags) > 0) {
                        $tagsKey = md5(json_encode(array_values($decodedTags)));

                        if (!isset($postsByTagsCache[$tagsKey])) {
                            $postsByTagsCache[$tagsKey] = FormMasterTitle::query()
                                ->select('cms_form_mstr_title.id', 'cms_form_mstr_title.cfmt_title')
                                ->leftJoin(DB::raw('STX_PORTAL.dbo.portal_gencode_mstr as pgTags'), function ($join) {
                                    $join->on(DB::raw('STX_CMS.dbo.cms_form_mstr_title.id'), '=', 'pgTags.pgm_value')
                                        ->where('pgTags.pgm_code', 'FP_TAGS_LIST')
                                        ->whereNotNull('pgTags.pgm_value2');
                                })
                                ->join(DB::raw('STX_PORTAL.dbo.portal_gencode_mstr as pg'), function ($join) {
                                    $join->on(DB::raw('STX_CMS.dbo.cms_form_mstr_title.id'), '=', 'pg.pgm_value')
                                        ->where('pg.pgm_code', 'FP_PUBLISH_POSTS')
                                        ->whereNotNull('pg.pgm_value2');
                                })
                                ->where('cms_form_mstr_title.cfmt_quiz_flag', 3)
                                ->whereIn('pgTags.pgm_value2', array_values($decodedTags))
                                ->orderBy('cms_form_mstr_title.created_at', 'desc')
                                ->limit(5)
                                ->get()
                                ->toArray();
                        }

                        $resultForm = [];
                        foreach ($postsByTagsCache[$tagsKey] as $value) {
                            $postId = (int) ($value['id'] ?? 0);
                            if ($postId <= 0) {
                                continue;
                            }

                            $resultForm[] = [
                                'idx' => (string) ($value['id'] ?? $navItem['idx'] ?? ''),
                                'value' => (string) ($value['id'] ?? $navItem['idx'] ?? ''),
                                'label' => $value['cfmt_title'] ?? '',
                                'icon' => 'label',
                                'type' => 'page',
                                'linkto' => (string) ($value['id'] ?? '#'),
                                'page' => (string) ($value['id'] ?? '#'),
                                'url' => (string) ($value['id'] ?? '#'),
                                // Keep payload lightweight to avoid OOM on big nav trees
                                'forms' => [],
                            ];
                        }

                        $navItem['children'] = $resultForm;
                    }
                }
            }

            unset($navItem);
            return $items;
        };

        $data = $processTree($data);

        if (empty($data)) {
            return $this->handleError('No navigation menu found', 404);
        } else {
            return $this->handleResponse($data, 'Navigation menu retrieved successfully');
        }
    }

    public function saveNavMenu(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'label' => 'nullable|string',
            'icon' => 'nullable|string',
            'dmsShared' => 'nullable|boolean',
        ]);

        $resolvedParent = !empty(trim((string) $request->parent))
            ? trim((string) $request->parent)
            : null;

        $existingNav = null;
        if (!empty($request->id)) {
            $existingNav = PortalGencode::where('pgm_code', 'FP_NAV')
                ->where('id', $request->id)
                ->first();

            if (!$existingNav) {
                return $this->handleError('Navigation menu not found', 404);
            }
        }

        $getLastOrder = PortalGencode::where('pgm_code', 'FP_NAV')
            ->where('pgm_parent', $resolvedParent)
            ->when($existingNav, function ($query) use ($existingNav) {
                $query->where('id', '<>', $existingNav->id);
            })
            ->orderBy('pgm_order', 'desc')
            ->first();

        $resolvedOrder = $existingNav
            ? (
                (string) ($existingNav->pgm_parent ?? '') === (string) ($resolvedParent ?? '')
                ? $existingNav->pgm_order
                : (($getLastOrder->pgm_order ?? 0) + 1)
            )
            : (($getLastOrder->pgm_order ?? 0) + 1);

        $linkValue = $data['type'] === 'page'
            ? ($request->page !== null ? (string) $request->page : null)
            : ($request->url ?? null);

        $resolvedLabel = $data['label'] ?? ($existingNav->pgm_value ?? null);
        $resolvedIcon = $data['icon'] ?? ($existingNav->pgm_value2 ?? null);
        $resolvedValue = $linkValue ?? ($existingNav->pgm_value3 ?? null);

        $gencode = PortalGencode::updateOrCreate(
            [
                'id' => $request->id ?? null,
                'pgm_code' => 'FP_NAV',
            ],
            [
                'pgm_code' => 'FP_NAV',
                'pgm_value' => $resolvedLabel,
                'pgm_value2' => $resolvedIcon,
                'pgm_desc' => $data['type'],
                'pgm_value3' => $resolvedValue,
                'pgm_desc2' => $request->has('tags') && !empty($request->tags) ? json_encode($request->tags) : null,
                'pgm_desc3' => isset($data['dmsShared']) && $data['dmsShared'] == true ? '1' : '0',
                'pgm_parent' => $resolvedParent,
                'pgm_order' => $resolvedOrder,
            ]
        );

        $this->clearNavMenuCache();

        return $this->handleResponse($gencode, 'Navigation menu saved successfully');
    }
    public function deleteNavMenu($id)
    {
        $gencode = PortalGencode::where('id', $id)->first();
        if (!$gencode) {
            return $this->handleError('Navigation menu not found', 404);
        }

        // Also delete all children with pgm_parent = $id
        PortalGencode::where('pgm_parent', $id)->delete();
        $gencode->delete();

        $this->clearNavMenuCache();

        return $this->handleResponse([], 'Navigation menu deleted successfully');
    }

    public function getNavConf($id = '')
    {
        $data = $this->getDataGencode('FP_NAV_LIST_CONF', !empty($id) ? ['id' => $id] : [], [
            'keys' => 'pgm_value',
            'config' => 'pgm_value2',
            'url' => 'pgm_value3',
            'label' => 'pgm_desc',
            'method' => 'pgm_desc2',
            'order' => 'pgm_parent',
            'color' => 'pgm_desc3',
            'idx' => 'id',
            'children' => 'children',
        ], [], false, true);

        if (empty($data)) {
            return $this->handleError('No navigation configuration found', 404);
        } else {
            return $this->handleResponse($data, 'Navigation configuration retrieved successfully');
        }
    }

    public function updateOrderNav(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'order' => 'required|integer',
            'parentID' => 'nullable|integer',
        ]);

        $datas = PortalGencode::where('id', $data['id'])->where('pgm_code', 'FP_NAV')->first();

        if (!$datas) {
            return $this->handleError('Navigation item not found', 404);
        } else {
            $checkPrevOrder = PortalGencode::where('pgm_parent', $data['parentID'])
                ->where('pgm_code', 'FP_NAV')
                ->where('pgm_order', $data['order'])
                ->first();

            if ($checkPrevOrder) {
                $checkPrevOrder->pgm_order = $datas->pgm_order;
                $checkPrevOrder->save();
            }

            $datas->pgm_order = $data['order'];

            $datas->save();
        }

        PortalGencode::where('pgm_parent', $data['parentID'])
            ->where('pgm_code', 'FP_NAV')
            ->orderBy('pgm_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()->each(function ($item, $index) {
                $item->pgm_order = $index + 1;
                $item->save();
            });

        $this->clearNavMenuCache();


        return $this->handleResponse([], 'Navigation order updated successfully');
    }

    public function updateMainPage($id, $state)
    {
        $gencode = PortalGencode::updateOrCreate(
            [
                'pgm_value' => $id,
                'pgm_code' => 'URL_PAGE_GEN'
            ],
            ['pgm_value2' => $state]
        );

        // return $gencode;

        // Update Home Page nav menu to point to this page if state is 1 (active)
        $this->saveNavMenu(new Request([
            'id' => '116',
            'type' => 'page',
            'page' => $id,
        ]));

        PortalGencode::where('pgm_code', 'URL_PAGE_GEN')
            ->where('pgm_value', '<>', $id)
            ->update(['pgm_value2' => '0']); // Ensure '0' is a non-null string

        return $this->handleResponse($gencode, 'Main page content updated successfully');
    }

    public function getMainConf($id = '')
    {
        $data = $this->getDataGencode('FP_GENERAL_CONF', !empty($id) ? ['id' => $id] : [], [
            'keys' => 'pgm_value',
            'config' => 'pgm_value2',
            'url' => 'pgm_value3',
            'label' => 'pgm_desc',
            'method' => 'pgm_desc2',
            'color' => 'pgm_desc3',
            'idx' => 'id',
            'children' => 'children',
            'parent' => 'pgm_parent',
        ], [], false, true);

        if (empty($data)) {
            return $this->handleError('No navigation configuration found', 404);
        } else {
            return $this->handleResponse($data, 'Navigation configuration retrieved successfully');
        }
    }

    public function saveMainConf(Request $request)
    {
        $data = $request->input('data', []);

        if (!is_array($data) || empty($data)) {
            return $this->handleError('No configuration data provided', 400);
        }

        // Validation rules for each config item
        $rules = [
            '*.keys' => 'required|string',
            '*.config' => 'nullable|string',
            '*.url' => 'nullable|string',
            '*.method' => 'nullable|string',
            '*.color' => 'nullable|string',
            '*.children' => 'nullable|array',
            '*.parent' => 'nullable',
        ];

        // Validate the array of configs
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.keys' => 'required|string',
            'data.*.config' => 'nullable|string',
            'data.*.url' => 'nullable|string',
            'data.*.method' => 'nullable|string',
            'data.*.color' => 'nullable|string',
            'data.*.children' => 'nullable|array',
            'data.*.parent' => 'nullable',
        ]);

        // Recursive function to save config and children
        $saveConfig = function ($configs, $parentId = null) use (&$saveConfig) {
            foreach ($configs as $item) {
                $gencode = PortalGencode::updateOrCreate(
                    [
                        'pgm_code' => 'FP_GENERAL_CONF',
                        'pgm_value' => $item['keys'],
                        'pgm_parent' => $item['parent'] ?? $parentId,
                    ],
                    [
                        'pgm_value2' => $item['config'] ?? null,
                        'pgm_value3' => $item['url'] ?? null,
                        'pgm_desc' => isset($item['label']) ? ($item['label'] === null ? ' ' : $item['label']) : '',
                        'pgm_desc2' => $item['method'] ?? null,
                        'pgm_desc3' => $item['color'] ?? null,
                        'pgm_parent' => $item['parent'] ?? $parentId,
                    ]
                );
                if (isset($item['children']) && is_array($item['children']) && count($item['children']) > 0) {
                    $saveConfig($item['children'], $gencode->id);
                }
            }
        };

        $saveConfig($data);

        return $this->handleResponse([], 'Main configuration saved successfully');
    }

    public function saveTags(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'tags' => 'required|array',
        ]);

        $getTags = [];
        foreach ($data['tags'] as $tag) {
            $getTags[] = PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'FP_TAGS_LIST',
                    'pgm_value' => $request->id,
                ],
                [
                    'pgm_value' => $request->id,
                    'pgm_value2' => $tag,
                    'pgm_desc' => 'Tags assignment',
                ]
            )->toArray();
        }

        return $this->handleResponse($getTags, 'Tags saved successfully');
    }

    public function saveHashTags(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'hashtags' => 'required|array',
        ]);

        $getTags = [];
        foreach ($data['hashtags'] as $tag) {
            $getTags[] = PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'FP_HASHTAGS_LIST',
                    'pgm_value' => $request->id,
                ],
                [
                    'pgm_value' => $request->id,
                    'pgm_value2' => $tag,
                    'pgm_desc' => 'Hashtags assignment',
                ]
            )->toArray();
        }

        return $this->handleResponse($getTags, 'Hashtags saved successfully');
    }

    public function removeHashTag($id, $tag)
    {
        $gencode = PortalGencode::where('pgm_code', 'FP_HASHTAGS_LIST')
            ->where('pgm_value', $id)
            ->where(DB::raw('CAST(pgm_value2 AS VARCHAR)'), $tag)
            ->first();

        if (!$gencode) {
            return $this->handleError('Hashtag not found', 404);
        }

        $gencode->delete();

        return $this->handleResponse([], 'Hashtag removed successfully');
    }

    public function removeTag($id, $tag)
    {
        $gencode = PortalGencode::where('pgm_code', 'FP_TAGS_LIST')
            ->where('pgm_value', $id)
            ->where(DB::raw('CAST(pgm_value2 AS VARCHAR)'), $tag)
            ->first();

        if (!$gencode) {
            return $this->handleError('Tag not found', 404);
        }

        $gencode->delete();

        return $this->handleResponse([], 'Tag removed successfully');
    }

    public function getPosts(Request $request)
    {
        $data = $this->getDataGencode('FP_POSTS', [], [
            'value' => 'pgm_value',
            'label' => 'pgm_desc',
            'icon' => 'pgm_value2',
            'index' => 'pgm_value3',
        ]);

        usort($data, function ($a, $b) {
            return ($a['index'] ?? 0) <=> ($b['index'] ?? 0);
        });

        if (empty($data)) {
            return $this->handleError('No posts found', 404);
        } else {
            return $this->handleResponse($data, 'Posts retrieved successfully');
        }
    }

    public function publishPost(Request $request, $id, $state = 0)
    {
        $data = PortalGencode::updateOrCreate(
            [
                'pgm_code' => 'FP_PUBLISH_POSTS',
                'pgm_value' => $id,
            ],
            [
                'pgm_value' => $id,
                'pgm_value2' => $state == 1 ? date('Y-m-d H:i:s') : null,
                'pgm_desc' => $state == 1 ? "Post $id published" : 'Post unpublished',
            ]
        );

        if ($state == 1) {
            $getPublishedData = $this->getDataGencode(
                'FP_PUBLISH_POSTS',
                [
                    'pgm_value' => $id,
                ],
                [],
                [],
                true
            );

            // return $getPublishedData;

            $listNotifData = [];

            $formController = app(FormController::class);
            $dataForm = $formController->viewByID((int) $id)->getOriginalContent()['data']['value'] ?? [];

            // return $dataForm['hashtags'] ?? [];

            // get list notification by category
            foreach ($dataForm['tags'] ?? [] as $category) {
                $dataSubscriberByCategory = $this->getSubscribedData(
                    'categories',
                    $category
                );

                $listNotifData = array_merge($listNotifData, $dataSubscriberByCategory);
            }

            // return $listNotifData;

            // Get list notification by creator
            $dataSubscriberByCreator = $this->getSubscribedData(
                'users',
                $dataForm['p_u_username'] ?? ''
            );

            if (count($dataSubscriberByCreator) > 0) {
                $listNotifData = array_merge($listNotifData, $dataSubscriberByCreator);
            }

            // Get list notification by all hashtags
            $dataSubscriberByHashtags = $this->getSubscribedData(
                'tags',
                '',
                $dataForm['hashtags'] ?? []
            );

            if (count($dataSubscriberByHashtags) > 0) {
                $listNotifData = array_merge($listNotifData, $dataSubscriberByHashtags);
            }

            $getUsersDetail = $getListActiveUsers = app(UsersController::class)->userActiveOnly($dataForm['p_u_username'])->getOriginalContent()['data'][0] ?? null;

            // return $listNotifData;
            foreach ($listNotifData as $keyNotif => $valueNotif) {
                $subscriber = $valueNotif['subscriber'] ?? '';

                if ($subscriber === '_ALL') {
                    $getListActiveUsers = app(UsersController::class)->userActiveOnly()->getOriginalContent()['data'] ?? [];
                } else {
                    $getListActiveUsers = User::query()
                        ->with(['det' => function ($query) {
                            $query->where('pud_is_active', 1);
                        }])
                        ->whereHas('det', function ($query) {
                            $query->where('pud_is_active', 1);
                        })
                        ->where(function ($query) use ($subscriber) {
                            $query->where('username', $subscriber)
                                ->orWhere('email', $subscriber);
                        })
                        ->orderBy('email')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'username' => $item->username,
                                'email' => $item->email,
                                'pud_first_name' => $item->det ? $item->det->pud_first_name : null,
                                'pud_last_name' => $item->det ? $item->det->pud_last_name : null,
                            ];
                        })
                        ->values()
                        ->toArray();

                    if (empty($getListActiveUsers) && Str::contains($subscriber, '@')) {
                        $getListActiveUsers = [[
                            'username' => $subscriber,
                            'email' => $subscriber,
                        ]];
                    }
                }

                foreach ($getListActiveUsers as $keyUser => $valueUser) {
                    notifSentQueue::dispatch(
                        $dataForm['p_u_username'],
                        $valueUser['username'],
                        'New Post Published : ' . ($dataForm['title'] ?? 'Untitled'),
                        //'A new post has been published in by ' . ($getUsersDetail ? $getUsersDetail['pud_first_name'] . ' ' . $getUsersDetail['pud_last_name'] : 'Unknown') . '. Check it out!<br><br>Title: ' . ($dataForm['title'] ?? 'Untitled') . '<br>Category: ' . implode(', ', $dataForm['tags'] ?? []) . '<br><br>' . $this->extractHtmlPreview($dataForm['forms'] ?? []),
                        'A new post has been published on our Intranet.',
                        date('Y-m-d H:i:s'),
                        null,
                        'post',
                        'email',
                        env('FE_URL') . '/pages/' . ($dataForm['tags'][0] ?? 'uncategorized') . '/' . $dataForm['url'],
                        '',
                        '',
                        $request->graph ?? null
                    )->onQueue('notifications');
                }
            }
        }

        // return $listNotifData;

        return $this->handleResponse($data, 'Post published successfully');
    }

    public function extractHtmlPreview($forms)
    {
        $preview = '';

        foreach ($forms as $form) {
            // initialize/clear last header image
            $this->headerImage = $this->headerImage ?? null;

            $preview = '';

            // recursive search for first HTML content (handles rows -> content arrays)
            $findHtml = function ($items) use (&$findHtml, &$preview) {
                foreach ($items as $it) {
                    if (!is_array($it)) {
                        continue;
                    }

                    // direct html block
                    if (isset($it['type']) && $it['type'] === 'html' && isset($it['content'])) {
                        $html = (string) $it['content'];

                        // extract first image src if present
                        libxml_use_internal_errors(true);
                        $doc = new \DOMDocument();
                        // ensure proper encoding to avoid warnings
                        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                        $imgs = $doc->getElementsByTagName('img');
                        if ($imgs->length > 0) {
                            $src = $imgs->item(0)->getAttribute('src');
                            // store header image on the controller for later use
                            $this->headerImage = $src;
                        }
                        // extract text preview (first 200 chars)
                        $preview = substr(trim(strip_tags($html)), 0, 200) . '...';
                        return true; // found, stop recursion
                    }

                    // nested content (e.g., row -> content array)
                    if (isset($it['content']) && is_array($it['content']) && count($it['content']) > 0) {
                        if ($findHtml($it['content'])) {
                            return true;
                        }
                    }
                }
                return false;
            };

            // start search
            if (is_array($forms) && count($forms) > 0) {
                $findHtml($forms);
            }

            // $preview will be returned below (preserving original function return type)
        }

        return $preview;
    }

    public function getSubscribedData($type, $value, $subscriber = '')
    {
        // get list notification by category
        return $this->getDataGencode(
            'FP_SUBSCRIBE_POSTS',
            $subscriber ?
            [
                'pgm_value' => $type,
                'pgm_value2' => $value,
                'pgm_value3' => $subscriber,
                'pgm_desc2' => '1'
            ]
            : [
                'pgm_value' => $type,
                'pgm_value2' => $value,
            ],
            [
                'type' => 'pgm_value|string',
                'value' => 'pgm_value2|string',
                'subscriber' => 'pgm_value3|string',
            ]
        );
    }

    public function copyPost($id)
    {
        $post = PortalGencode::where('pgm_code', 'FP_POSTS')
            ->where('pgm_value', $id)
            ->first();

        if (!$post) {
            return $this->handleError('Post not found', 404);
        }

        $newPost = $post->replicate();
        $newPost->pgm_value = null; // Reset the value to create a new post
        $newPost->save();

        return $this->handleResponse($newPost, 'Post copied successfully');
    }

    public function getNavAssignedDMS()
    {
        $data = $this->getDataGencode('FP_NAV', ['pgm_desc3' => '1'], [
            'idx' => 'id',
            'value' => 'id',
            'label' => 'pgm_value',
            'icon' => 'pgm_value2',
            'type' => 'pgm_desc',
            'linkto' => 'pgm_value3',
            'page' => 'pgm_value3',
            'url' => 'pgm_value3',
            'children' => 'children',
            'parent' => 'pgm_parent',
            'tags' => 'pgm_desc2',
            'dmsShared' => 'pgm_desc3|bool',
        ], [
            'pgm_order' => 'asc',
            'id' => 'asc'
        ], false, true, true, withParents: true);

        // return $data;
        $hasil = [];
        foreach ($data as $key => $value) {
            $pageId = $this->normalizeIntId($value['page'] ?? null);

            $getCMSForms = $pageId
                ? FormMaster::where('cfmt_id', $pageId)->where('cfm_type', 'files')->get()->toArray()
                : [];

            $dataCMS = [];
            foreach ($getCMSForms as $keyData => $valueData) {
                $dataCMS[] = array_merge(
                    $valueData,
                    [
                        'cfm_content' => json_decode($valueData['cfm_content'], true),
                    ]
                );
            }

            $dataTemp = [
                'id' => $value['idx'],
                'label' => $value['label'],
                'icon' => $value['icon'],
                'idPage' => $value['page'],
                'forms' => $dataCMS,
                'parent' => !empty($value['parent'])
                    ? $this->getDataGencode(
                        'FP_NAV',
                        [
                            // 'pgm_desc3' => '1',
                            'id' => (int)$value['parent']
                        ],
                        [
                            'value' => 'id',
                            'label' => 'pgm_value',
                            'icon' => 'pgm_value2',
                        ],
                        firstSelect: true
                    )
                    : null
            ];

            if (isset($value['children'])) {
                $dataTemp = array_merge($dataTemp, [
                    'children' => $value['children']
                ]);
            }

            $hasil[] = $dataTemp;
        }

        if (empty($data)) {
            return $this->handleError('No navigation menu found', 404);
        } else {
            return $this->handleResponse($hasil, 'Navigation menu retrieved successfully');
        }
    }

    public function saveDMStoFrontPage(Request $request)
    {
        $data = $request->validate([
            'idPage' => 'required|array',
            'idPage.*' => 'required|string',
            'sharedData' => 'nullable|array',
            'options' => 'required|array',
            'options.createPage' => 'required|boolean',
            'userId' => 'required|string',
            'editedShared' => 'array',
        ]);

        // return $data;

        // Check Navigation Menu and get the forms
        $listSelectedNav = [];
        foreach ($data['idPage'] as $idPage) {
            $getNav = $this->getNavMenu([], true, $idPage)->getOriginalContent();
            if ($getNav['status']) {
                $getEditedShared = array_filter($data['editedShared'], function ($item) use ($idPage) {
                    return $item['formId'] == $idPage;
                });

                $getNavDetail = $this->getDataGencode('FP_NAV', ['id' => $idPage], [
                    'code' => 'pgm_code',
                    'name' => 'pgm_value',
                    'icon' => 'pgm_value2',
                    'idForm' => 'pgm_value3',
                    'type' => 'pgm_desc',
                    'parent' => 'pgm_parent',
                ], [], true) ?? null;


                $getNavDetailID = $this->normalizeIntId($getNavDetail['idForm'] ?? null);
                // return $data['options']['createPage'];

                // Combine existing files with new sharedData
                if (!empty($getEditedShared)) {
                    $editedSharedItem = array_values($getEditedShared)[0];
                    $mergedFiles = array_merge($editedSharedItem['files'] ?? [], $data['sharedData']);
                    $editedSharedItem['files'] = $mergedFiles;
                    // $listSelectedNav[] = $editedSharedItem;
                }

                $formMaster = $getNavDetailID
                    ? FormMaster::where('cfmt_id', $getNavDetailID)
                        ->where('cfm_type', 'files')
                        ->first()
                    : null;

                if ($formMaster) {
                    $formMaster->update([
                        'cfm_content' => json_encode([
                            'files' => $editedSharedItem['files'] ?? [],
                            'orderBy' => 'created_at',
                            'order' => 'desc',
                            'layout' => 'grid',
                            'maxShow' => 5,
                            'username' => 'deny-rachmat@sumitronics.co.jp',
                            'mode' => 'all',
                        ]),
                    ]);

                    $idForm = FormMasterTitle::where('id', $formMaster->cfmt_id)->first()->id ?? null;
                } else {
                    $storeHeaderForm = FormMasterTitle::create([
                        'cfmt_title' => 'DMS Shared - ' . ($getNavDetail['name'] ?? 'No Name'),
                        'cfmt_quiz_flag' => 2,
                        'p_u_username' => $request->header('username'),
                    ]);

                    $storeRow = FormMaster::create([
                        'cfmt_id' => $storeHeaderForm->id,
                        'cfm_type' => 'row',
                        'cfm_seq_name' => '1',
                        'cfm_parent_id' => 0,
                        'p_u_username' => $request->header('username'),
                    ]);

                    $formsCreate = FormMaster::create([
                        'cfmt_id' => $storeHeaderForm->id,
                        'cfm_type' => 'files',
                        'cfm_content' => json_encode([
                            'files' => $data['sharedData'] ?? [],
                            'orderBy' => 'created_at',
                            'order' => 'desc',
                            'layout' => 'grid',
                            'maxShow' => 5,
                            'username' => '',
                            'mode' => 'all',
                        ]),
                        'cfm_parent_id' => $storeRow->id,
                        'cfm_seq_name' => '1',
                        'p_u_username' => $request->header('username'),
                    ]);

                    $idForm = $storeHeaderForm->id;
                }

                PortalGencode::updateOrCreate(
                    [
                        'pgm_code' => 'FP_NAV',
                        'pgm_value' => $getNavDetail['name'],
                        // 'pgm_value3' => (string)$idForm,
                    ],
                    [
                        'pgm_code' => 'FP_NAV',
                        'pgm_value' => $getNavDetail['name'],
                        'pgm_value2' => $getNavDetail['icon'] ?? null,
                        'pgm_value3' => (string) $idForm,
                        'pgm_desc' => 'page',
                        'pgm_parent' => !empty(trim($getNavDetail['parent'])) ? trim($getNavDetail['parent']) : null,
                        'pgm_desc3' => '1', // Mark as DMS Shared
                    ]
                );

                // If Create new navigation based on files
                if ($data['options']['createPage']) {
                    foreach ($data['sharedData'] as $keyFiles => $valueFiles) {
                        if ($valueFiles['type'] == 'file') {
                            $getSharedFiles = DMSShareDet::where('ddm_id', $valueFiles['id'])->first();

                            if (!empty($getSharedFiles)) {
                                $storeHeaderForm = FormMasterTitle::create([
                                    'cfmt_title' => 'DMS Shared - ' . ($valueFiles['editValue'] ?? $valueFiles['ddm_doc_name']),
                                    'cfmt_quiz_flag' => 2,
                                    'p_u_username' => $request->header('username'),
                                ]);

                                $storeRow = FormMaster::create([
                                    'cfmt_id' => $storeHeaderForm->id,
                                    'cfm_type' => 'row',
                                    'cfm_seq_name' => '1',
                                    'cfm_parent_id' => 0,
                                    'p_u_username' => $request->header('username'),
                                ]);

                                $formsCreate = FormMaster::create([
                                    'cfmt_id' => $storeHeaderForm->id,
                                    'cfm_type' => 'html',
                                    'cfm_content' => '<p><iframe width="100%" style="border: none;height: 80vh;" src="https://mozilla.github.io/pdf.js/web/viewer.html?file=https%3A%2F%2Fapi.sumitronics-indonesia.com%2Fapi%2Fdms%2FdocumentsRoots%2FgetSharedFilesFolder%2F' . $getSharedFiles->ddfus_token . '%2F' . $getSharedFiles->id . '"></iframe></p>',
                                    'cfm_parent_id' => $storeRow->id,
                                    'cfm_seq_name' => '1',
                                    'p_u_username' => $request->header('username'),
                                ]);

                                PortalGencode::updateOrCreate(
                                    [
                                        'pgm_code' => 'FP_NAV',
                                        'pgm_value' => $valueFiles['editValue'] ?? $valueFiles['ddm_doc_name'],
                                        'pgm_value3' => (string) $storeRow->id,
                                    ],
                                    [
                                        'pgm_code' => 'FP_NAV',
                                        'pgm_value' => $valueFiles['editValue'] ?? $valueFiles['ddm_doc_name'],
                                        'pgm_value2' => 'file_open',
                                        'pgm_value3' => (string) $storeHeaderForm->id,
                                        'pgm_desc' => 'page',
                                        'pgm_parent' => !empty(trim($idPage)) ? trim(trim($idPage)) : null,
                                        'pgm_desc3' => '0', // Mark as DMS Shared
                                    ]
                                );
                            }
                        }
                    }
                }

                $listSelectedNav[] = $getNavDetailID;
            }
        }

        // return $listSelectedNav;
        // Process the data as needed, e.g., save to database
        // For demonstration, we'll just return the received data

        return $this->handleResponse($listSelectedNav, 'DMS items saved to front page successfully');
    }

    public function subscribePosts(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:users,categories,tags,_all',
            'id' => 'required|string',
            'user_id' => 'required|string',
        ]);

        PortalGencode::updateOrCreate(
            [
                'pgm_code' => 'FP_SUBSCRIBE_POSTS',
                'pgm_value' => $validated['type'],
                'pgm_value2' => $validated['id'],
                'pgm_value3' => $validated['user_id'],
            ],
            [
                'pgm_code' => 'FP_SUBSCRIBE_POSTS',
                'pgm_value' => $validated['type'],
                'pgm_value2' => $validated['id'],
                'pgm_value3' => $validated['user_id'],
                'pgm_desc' => 'Subscription to post',
            ]
        );

        return response()->json(['message' => 'Subscribed to post successfully'], 200);
    }

    public function updateBulkSubscribePosts(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|array',
            'status' => 'required|in:0,1,2',
            'type' => 'required|string|in:users,categories,tags,_all',
        ]);

        foreach ($validated['email'] as $subscription) {
            $valueDatas = $validated['type'] === 'users'
                ? $request->valAuthor
                : ($validated['type'] === 'categories'
                    ? $request->valCategories
                    : $request->valHashtags);

            PortalGencode::where('pgm_code', 'FP_SUBSCRIBE_POSTS')
                ->where('pgm_value', $validated['type'])
                ->where('pgm_value3', $subscription)
                ->delete();

            foreach ($valueDatas as $key => $valueData) {
                PortalGencode::updateOrCreate(
                    [
                        'pgm_code' => 'FP_SUBSCRIBE_POSTS',
                        'pgm_value' => $validated['type'],
                        'pgm_value2' => $valueData,
                        'pgm_value3' => $subscription,
                    ],
                    [
                        'pgm_code' => 'FP_SUBSCRIBE_POSTS',
                        'pgm_value' => $validated['type'],
                        'pgm_value2' => $valueData,
                        'pgm_value3' => $subscription,
                        'pgm_desc' => 'Subscription to post',
                        'pgm_desc2' => $validated['status']
                    ]
                );
            }
        }

        return response()->json(['message' => 'Bulk subscriptions updated successfully'], 200);
    }

    public function subscribe(Request $request)
    {
        $user = User::where('username', $request->header('username'))->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        // Validasi data dari frontend
        $request->validate([
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required',
        ]);

        // Simpan data subscription ke tabel push_subscriptions
        $user->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );

        return response()->json(['success' => true, 'message' => 'Subscribed to push notifications successfully.'], 200);
    }

    public function unsubscribe(Request $request)
    {
        $user = User::where('username', $request->header('username'))->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        // Validasi data dari frontend
        $request->validate([
            'endpoint' => 'required',
        ]);

        // Hapus data subscription dari tabel push_subscriptions
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true, 'message' => 'Unsubscribed from push notifications successfully.'], 200);
    }
}
