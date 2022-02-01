<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    public function getMyMemo() {
        // もしクエリパラメータtagがあれば、タグで絞り込み
            // タグがなければ全て取得
            $query_tag = \Request::query('tag');
            // ==== ベースのメソッド ====
            $query = Memo::query()->select('memos.*')
                    ->where('user_id', '=', \Auth::id())
                    ->whereNull('deleted_at')
                    ->orderBy('updated_at', 'DESC');
             // ==== ベースのメソッドここまで ====

             // もしクエリパラメータtagがあれば
            if(!empty($query_tag)){
                // タグで絞り込み
                $query->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
                      ->where('memo_tags.tag_id', '=', $query_tag);

            }else{
                // 全て表示
                $memos = Memo::select('memos.*')
                    ->where('user_id', '=', \Auth::id())
                    ->whereNull('deleted_at')
                    ->orderBy('updated_at', 'DESC') //ASC=昇順、 DESC=降順
                    ->get();
            }

            $memos = $query->get();

            return $memos;
    }
}
