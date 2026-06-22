<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\CoinTradeResource;
use App\Models\Coin\CoinTrade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * 金币交易日志
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class CoinTradeController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('permission:coin_trades.index')->only(['index']);
    }

    /**
     * 交易记录
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CoinTrade::query()
            ->with(['user'])
            ->orderByDesc('id');
        $items = $query->paginate(per_page($request));

        return CoinTradeResource::collection($items);
    }
}
