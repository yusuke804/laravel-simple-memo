<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Memo;
use App\Models\Tag;

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


            // 自分のメモ取得はmemoモデルに任せる
            // Memoモデルに記述した関数を使用するためには、インスタンス化をしなければならない
            $memo_model = new Memo();
            //メモ取得
            $memos = $memo_model->getMyMemo();


            $tags = Tag::where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->get();

        $view->with('memos', $memos)->with('tags', $tags);
        });
    }
}
