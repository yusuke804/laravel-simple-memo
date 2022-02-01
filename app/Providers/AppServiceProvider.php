<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Memo;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 全てのメソッドが呼ばれる前に先に呼ばれるメソッド
        // 今回は、メモ一覧を表示するにあたって、一覧画面と編集画面で重複している処理をひとまとめ（共通化）する
        view()->composer('*', function ($view) {
            $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC') //ASC=昇順、 DESC=降順
            ->get();

        $view->with('memos', $memos);
        });
    }
}
