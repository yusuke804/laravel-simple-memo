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
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC') //ASC=昇順、 DESC=降順
            ->get();

        $tags = Tag::where('user_id', '=', \Auth::id())
        ->whereNull('deleted_at')
        ->orderBy('id', 'DESC')
        ->get();




        return view('create', compact('memos', 'tags'));
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
            foreach($posts['tags'] as $tag){
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
            }
        });
        //-----ここまで
        return redirect( route('home') );
    }

    public function edit($id)
    {
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC') //ASC=昇順、 DESC=降順
            ->get();

        $edit_memo = Memo::find($id);


        return view('edit', compact('memos', 'edit_memo'));
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        //dd($posts);
        //dd dump dieの略　メソッドの引数に取った値を展開して止める　→　データの確認をするためのデバッグ関数

        Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);

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
