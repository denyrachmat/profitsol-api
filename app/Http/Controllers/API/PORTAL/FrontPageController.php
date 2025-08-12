<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\CMS\FormController;
use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Traits\PORTAL\GencodeTraits;
use App\Models\PORTAL\PortalGencode;
use Illuminate\Support\Facades\DB;

class FrontPageController extends BaseController
{
    use GencodeTraits;
    public function getFPMenu($id = '')
    {
        $data = $this->getDataGencode('FP_CONF_MENU', [], [
            'value' => 'pgm_value',
            'label' => 'pgm_desc',
            'icon' => 'pgm_value2',
            'index' => 'pgm_value3',
        ]);

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
        return $this->getNavMenu([], (bool) $showAll);
    }

    public function getNavMenu($data = [], $showAll = false)
    {
        if (empty($data)) {
            $data = $this->getDataGencode('FP_NAV', [], [
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
            ], [], false, true, $showAll);
        }

        $pages = $this->getDataGencode('URL_PAGE_GEN', [], [
            'value' => 'pgm_value',
            'url' => 'pgm_desc',
            'is_main' => 'pgm_value2',
            'desc' => 'pgm_desc2',
        ]);

        // return $pages;

        // Sort $pages by 'index' before merging
        usort($pages, function ($a, $b) {
            return ($a['index'] ?? 0) <=> ($b['index'] ?? 0);
        });

        $hasil = [];
        foreach ($data as &$navItem) {
            $filterData = array_filter($pages, function ($page) use ($navItem) {
                return $page['value'] == $navItem['linkto'];
            });
            $formController = new FormController();

            $navItem['is_main'] = isset($filterData) && count($filterData) > 0 ? array_values($filterData)[0]['is_main'] : 0;
            $navItem['pages'] = count($filterData) > 0 ? array_values($filterData)[0] : [];
            $navItem['forms'] = $formController->viewByID((int) $navItem['linkto'])->getOriginalContent()['data']['value'] ?? [];
            if (isset($navItem['children']) && count($navItem['children']) > 0 && is_array($navItem['children'])) {
                $navItem['children'] = $this->getNavMenu($navItem['children'])->getOriginalContent()['data'] ?? [];
            }

            if (!empty($navItem['tags'])) {
                $formController = app(FormController::class);
                $formShowData = $formController->show('post', base64_encode($navItem['tags']));
                $resultForm = [];
                foreach ($formShowData as $keyDataForms => $valueDataForms) {
                    // Push the requested object structure as an associative array
                    $value = $valueDataForms;
                    $resultForm[] = [
                        'idx' => (string)($value['id'] ?? $navItem['idx'] ?? ''),
                        'label' => $value['cfmt_title'] ?? '',
                        'icon' => 'label',
                        'type' => 'page',
                        'linkto' => (string)($value['id'] ?? '#'),
                        'page' => (string)($value['id'] ?? '#'),
                        'url' => (string)($value['id'] ?? '#'),
                        'forms' => $formController->viewByID((int) ($value['id']))->getOriginalContent()['data']['value'] ?? [],
                    ];
                }

                $navItem['children'] = $resultForm ?? [];
                // $navItem['children'] = $this->getNavMenu($navItem['children'])->getOriginalContent()['data'] ?? [];
            }
        }

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
            'label' => 'required|string',
            'icon' => 'nullable|string',
        ]);

        $gencode = PortalGencode::updateOrCreate(
            [
                'id' => $request->id ?? null,
                'pgm_code' => 'FP_NAV',
            ],
            [
                'pgm_code' => 'FP_NAV',
                'pgm_value' => $data['label'],
                'pgm_value2' => $data['icon'],
                'pgm_desc' => $data['type'],
                'pgm_value3' => $data['type'] === 'page' ? (string) $request->page : $request->url ?? null,
                'pgm_desc2' => json_encode($request->tags) ?? null,
                'pgm_parent' => $request->parent ?? null,
            ]
        );

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

    public function updateMainPage($id, $state)
    {
        $gencode = PortalGencode::updateOrCreate(
            [
                'pgm_value' => $id,
                'pgm_code' => 'URL_PAGE_GEN'
            ],
            ['pgm_value2' => $state]
        );

        PortalGencode::where('pgm_code', 'URL_PAGE_GEN')
            ->where('pgm_value', '<>', $id)
            ->update(['pgm_value2' => '0']); // Ensure '0' is a non-null string

        return $this->handleResponse($gencode, 'Main page content updated successfully');
    }

    public function getMainConf()
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

    public function publishPost($id, $state = 0)
    {
        $data = PortalGencode::updateOrCreate(
            [
                'pgm_code' => 'FP_PUBLISH_POSTS',
                'pgm_value' => $id,
            ],
            [
                'pgm_value' => $id,
                'pgm_value2' => $state == 1 ? date('Y-m-d H:i:s') : null,
                'pgm_desc' => "Post $id published",
            ]
        );

        return $this->handleResponse($data, 'Post published successfully');
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
}
