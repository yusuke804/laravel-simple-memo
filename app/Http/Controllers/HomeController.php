<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\models\Tag;
use App\models\MemoTag;
use DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $tags = Tag::where('user_id', '=', \Auth::id())
        ->whereNull('deleted_at')
        ->orderBy('id', 'DESC')
        ->get();

        return view('create', compact('tags'));
    }


    public function store(Request $request)
    {
        $posts = $request->all();

        //dd dump dieの略　メソッドの引数に取った値を展開して止める　→　データの確認をするためのデバッグ関数

        //-----ここからトランザクションを開始-----
        DB::transaction(function() use($posts) {
            //メモをインサート、さらに、memo_tagテーブル(メモとタグの中間テーブル)にインサートするための、メモのidを取得、memo_id変数に保存
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]);
            //すでに作成するタグが存在するかをチェックするための変数を用意する
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])
            ->exists();
            //新しいタグが入力されているか、すでに作成するタグが存在しているかをチェック
            if(!empty($posts['new_tag']) && !$tag_exists ) {
                //タグテーブルに新規タグを挿入すると同時に、memo_tagsテーブルに保存するためのtag_idも同時に変数に保管
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsテーブルに挿入
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
            //既存タグが紐づけられた場合→memo_tagsに挿入する
            if(!empty($posts['tags'][0])){
                foreach($posts['tags'] as $tag){
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }
        });
        //-----ここまで
        return redirect( route('home') );
    }

    public function edit($id)
    {
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
            ->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
            ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
            ->where('memos.user_id', '=', \Auth::id())
            ->where('memos.id', '=', $id)
            ->whereNull('memos.deleted_at')
            ->get();

        $include_tags = [];
        foreach($edit_memo as $memo){
            array_push($include_tags, $memo['tag_id']);
        }

        $tags = Tag::where('user_id', '=', \Auth::id())
        ->whereNull('deleted_at')
        ->orderBy('id', 'DESC')
        ->get();

        return view('edit', compact('edit_memo', 'include_tags', 'tags'));
    }

    public function update(Request $request)
    {
        $posts = $request->all();
        //dd($posts);
        //dd dump dieの略　メソッドの引数に取った値を展開して止める　→　データの確認をするためのデバッグ関数

        // トランザクションスタート
        DB::transaction(function() use($posts){
            Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);
            // 一旦、メモとタグの紐付けを削除
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();
            // 再度メモとタグの紐付け
            foreach ($posts['tags'] as $tag) {
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }
            // このユーザが作成した既存のタグの中に、新しく入力されたタグが存在すればtrueを返す変数を作成
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])
            ->exists();
            //新しいタグが入力されているか、すでに作成するタグが存在しているかをチェック
            if(!empty($posts['new_tag']) && !$tag_exists ) {
                //タグテーブルに新規タグを挿入すると同時に、memo_tagsテーブルに保存するためのtag_idも同時に変数に保管
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsテーブルに挿入
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }
        });
        // トランザクションここまで

        return redirect( route('home') );
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();


        //dd dump dieの略　メソッドの引数に取った値を展開して止める　→　データの確認をするためのデバッグ関数

        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time())]);

        return redirect( route('home') );
    }



}
