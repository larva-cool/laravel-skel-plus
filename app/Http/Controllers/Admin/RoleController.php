<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Admin\StoreAdminRoleRequest;
use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

/**
 * 角色管理
 *
 * @author Tongle Xu <xutongle@msn.com>
 */
class RoleController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('permission:roles.index')->only(['index']);
        $this->middleware('permission:roles.create')->only(['create', 'store']);
        $this->middleware('permission:roles.edit')->only(['edit', 'update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = Role::query()->orderBy('id')->paginate(per_page($request, 15));

        return RoleResource::collection($items);
    }

    /**
     * xm-select 选择器
     */
    public function select(): JsonResponse
    {
        $items = Role::query()->select(['name as value', 'name'])->orderBy('id')->get();

        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRoleRequest $request): JsonResponse
    {
        /** @var Role $role */
        $role = Role::create($request->safe()->except('permissions'));
        $role->givePermissionTo($request->permissions);

        return $this->success(trans('system.create_success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreAdminRoleRequest $request, Role $role): JsonResponse
    {
        $role->update($request->safe()->except('permissions'));
        $role->syncPermissions($request->permissions);

        return $this->success(trans('system.update_success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->name == 'Super Admin') {
            return $this->fail(trans('system.default_role_cannot_delete'));
        }
        $role->delete();

        return $this->success(trans('system.delete_success'));
    }
}
