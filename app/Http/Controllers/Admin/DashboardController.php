<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\AdminResource;
use App\Models\Admin\Admin;
use App\Models\User;
use App\Models\User\UserStat;
use App\Support\AdminHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 后台首页
 *
 * @author Tongle Xu <xutongle@msn.com>
 */
class DashboardController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * 返回当前登录的用户资料
     */
    public function user(Request $request): AdminResource
    {
        return new AdminResource($request->user());
    }

    /**
     * 左侧菜单
     */
    public function menus(Request $request): JsonResponse
    {
        /** @var Admin $user */
        $user = $request->user();
        $menus = AdminHelper::getLeftMenus($user);

        return response()->json($menus);
    }

    /**
     * Display dashboard page.
     */
    public function info(): JsonResponse
    {
        // 今日新增用户数
        $todayUserCount = UserStat::getTodayRegistration();
        // 7天内新增用户数
        $day7UserCount = UserStat::getRecentDaysRegistration(7);
        // 30天内新增用户数
        $day30UserCount = UserStat::getRecentDaysRegistration(30);
        // 总用户数
        $userCount = User::query()->count();
        // mysql版本
        $mysqlVersion = 'unknown';
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() === 'mysql') {
                $version = DB::select('select VERSION() as version');
                $mysqlVersion = $version[0]->version ?? 'unknown';
            }
        } catch (\Exception $e) {

        }

        $day30Detail = [];
        $now = time();
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', $now - 24 * 60 * 60 * $i);
            $day30Detail[substr($date, 5)] = UserStat::query()
                ->where('stat_date', $date)
                ->sum('new_user_count');
        }

        return response()->json([
            'today_user_count' => $todayUserCount,
            'day7_user_count' => $day7UserCount,
            'day30_user_count' => $day30UserCount,
            'user_count' => $userCount,
            'laravel_version' => app()->version(),
            'laravel_environment' => app()->environment(),
            'mysql_version' => $mysqlVersion,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'day30_detail' => array_reverse($day30Detail),
        ]);
    }
}
