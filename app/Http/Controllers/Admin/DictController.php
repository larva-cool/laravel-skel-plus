<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Dict\StoreDictDataRequest;
use App\Http\Requests\Admin\Dict\StoreDictRequest;
use App\Http\Requests\Admin\Dict\UpdateDictRequest;
use App\Http\Requests\BatchDestroyRequest;
use App\Http\Requests\SwitchRequest;
use App\Http\Resources\Admin\DictResource;
use App\Models\System\Dict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * 字典管理
 *
 * @author Tongle Xu <xutongle@msn.com>
 */
class DictController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('permission:admins.index')->only(['index']);
        $this->middleware('permission:admins.create')->only(['create', 'store', 'createData', 'storeData']);
        $this->middleware('permission:admins.edit')->only(['edit', 'update', 'editData', 'updateStatus']);
        $this->middleware('permission:admins.delete')->only(['destroy', 'batchDestroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = per_page($request, 15);
        $query = Dict::query()->orderBy('order')->orderBy('id');
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
        }
        $items = $query->withCount(['children'])->orderBy('order')->paginate($perPage);

        return DictResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDictRequest $request): JsonResponse
    {
        Dict::create($request->validated());

        return $this->success(trans('system.create_success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeData(StoreDictDataRequest $request): JsonResponse
    {
        Dict::create($request->validated());

        return $this->success(trans('system.create_success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDictRequest $request, Dict $dict): JsonResponse
    {
        $dict->update($request->validated());

        return $this->success(trans('system.update_success'));
    }

    /**
     * 更新状态
     */
    public function updateStatus(SwitchRequest $request): JsonResponse
    {
        $dict = Dict::query()->where('id', $request->id)->firstOrFail();
        $dict->update($request->safe()->only('status'));

        return $this->success(trans('system.update_success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dict $dict): JsonResponse
    {
        if ($dict->children()->count() > 0) {
            return $this->fail('请先删除子字典');
        }
        $dict->delete();

        return $this->success(trans('system.delete_success'));
    }

    /**
     * 批量删除
     */
    public function batchDestroy(BatchDestroyRequest $request): JsonResponse
    {
        Dict::query()->whereIn('id', $request->ids)->delete();

        return $this->success(trans('system.delete_success'));
    }
}
