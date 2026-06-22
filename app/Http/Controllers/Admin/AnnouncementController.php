<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Admin\Announcement\UpdateAnnouncementRequest;
use App\Http\Requests\SwitchRequest;
use App\Http\Resources\Admin\AnnouncementResource;
use App\Models\Announcement\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * 公告控制器
 *
 * @author Tongle Xu <xutongle@msn.com>
 */
class AnnouncementController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('permission:announcements.index')->only(['index']);
        $this->middleware('permission:announcements.create')->only(['create', 'store']);
        $this->middleware('permission:announcements.edit')->only(['edit', 'update']);
        $this->middleware('permission:announcements.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = Announcement::query()->orderByDesc('id')->paginate(per_page($request));

        return AnnouncementResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        Announcement::create($request->validated());

        return $this->success(trans('system.create_success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->validated());

        return $this->success(trans('system.update_success'));
    }

    /**
     * 更新状态
     *
     * @param  SwitchRequest  $request
     * @return JsonResponse
     */
    public function updateStatus(SwitchRequest $request): JsonResponse
    {
        $dict = Announcement::query()->where('id', $request->id)->firstOrFail();
        $dict->update($request->safe()->only('status'));

        return $this->success(trans('system.update_success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return $this->success(trans('system.delete_success'));
    }
}
