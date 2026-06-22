<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace App\Support;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminMenu;

/**
 * 管理员助手
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class AdminHelper
{
    /**
     * 通过账号查找用户
     */
    public static function findForAccount(string $account): ?Admin
    {
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            return Admin::query()->whereNotNull('email')->where('email', $account)->first();
        } elseif (preg_match('/^1[2-9]\d{9}$/', $account)) {
            return Admin::query()->whereNotNull('phone')->where('phone', $account)->first();
        } else {
            return Admin::query()->whereNotNull('username')->where('username', $account)->first();
        }
    }

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
