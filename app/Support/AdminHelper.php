<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace App\Support;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminMenu;

class AdminHelper
{
    /**
     * 读取左侧菜单
     * @param  Admin  $admin
     * @return array|null
     */
    public static function getLeftMenus(Admin $admin): ?array
    {
        $permissions = $admin->getAllPermissions()->pluck('name')->toArray();

        // 获取所有菜单
        $menus = AdminMenu::query();
        // 筛选出有权限的菜单
        if ($permissions) {
            $menus->where(function ($query) use ($permissions) {
                $query->whereNull('permission_name')
                    ->orWhereIn('permission_name', $permissions);
            });
        }
        $items = $menus->orderByDesc('order')->orderBy('id')->get()->toArray();

        $formattedItems = [];
        foreach ($items as $item) {
            $item['parent_id'] = (int) $item['parent_id'];
            $item['name'] = $item['title'];
            $item['value'] = $item['id'];
            $item['icon'] = $item['icon'] ? "layui-icon {$item['icon']}" : '';
            $formattedItems[] = $item;
        }

        $tree = new TreeHelper($formattedItems);
        $treeItems = $tree->getTree();

        if (! app()->environment('production')) {
            $treeItems = array_merge($treeItems, AdminMenu::getDefaultMenus());
        }
        return $treeItems;
    }
}
