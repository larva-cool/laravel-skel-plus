<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\User\LoginSucceeded;
use App\Http\Requests\Admin\Auth\PasswordLoginRequest;
use App\Http\Resources\Admin\TokenResource;
use App\Jobs\User\DeleteAccessTokenJob;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

/**
 * 管理员
 *
 * @author Tongle Xu <xutongle@msn.com>
 */
class AuthController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin')->except(['login']);
        // 登录限速
        $throttle = 'throttle:'.settings('user.login_throttle', '6,1');
        $this->middleware($throttle)->only(['login']);
    }

    /**
     * 登录验证
     */
    public function login(PasswordLoginRequest $request): JsonResponse
    {
        $admin = $request->authenticate();
        $token = $admin->createDeviceToken('PC');
        Event::dispatch(new Login('admin', $admin, false));
        Event::dispatch(new LoginSucceeded($admin, $request->ip(), $request->server('REMOTE_PORT'), $request->userAgent()));

        return response()->json($token);
    }

    /**
     * 重新签发访问令牌
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $tokenModel = $request->user()->currentAccessToken();
        if ($request->user()->isFrozen()) {// 禁止掉的用户不允许登录
            $request->user()->flushTokens();
            validation_exception('code', __('auth.blocked'));
        }
        $token = $request->user()->createDeviceToken('PC');
        // 一分钟后删除当前这个Token
        DeleteAccessTokenJob::dispatch($tokenModel->token)->delay(now()->addMinutes(1));
        Event::dispatch(new Login('admin', $request->user(), false));
        Event::dispatch(new LoginSucceeded($request->user('admin'), $request->ip(), $request->server('REMOTE_PORT'), $request->userAgent()));

        return response()->json($token);
    }

    /**
     * 获取已经签发的所有 Token
     */
    public function tokens(Request $request): AnonymousResourceCollection
    {
        $items = $request->user()->tokens()
            ->orderByDesc('id')
            ->paginate(per_page($request));

        return TokenResource::collection($items);
    }

    /**
     * 销毁当前正在使用的 Token
     */
    public function destroyCurrentAccessToken(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * 撤销指定的 Token
     */
    public function destroyToken(Request $request, $tokenId): Response
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();
        if (! $token) {
            return response()->noContent(404);
        }
        $token->delete();

        return response()->noContent();
    }
}
