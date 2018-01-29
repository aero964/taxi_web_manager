<?php


// DBの書き出し処理

//検証実行用↓
//execEdit("1","status","true");

function execEdit($getKey, $mode, $contents){

	// パスワードの場合はSALT関数を用いてハッシュ化
	if($mode == "password"){
		$contents = password_hash($contents, PASSWORD_DEFAULT);
	}

	// もう一度JSONを読み出し
	$userProperties = file_get_contents("./dat/userProperties.json");

	// PHPの配列として格納
	$up = json_decode($userProperties, true);

	// up変数が配列であるかどうかの確認
	if(!is_array($up)){
		die("データベース更新中にエラーが発生したため，作業を中断しました。申し訳ありません。");
	}

	/** 配列処理 **/

		// 与えられた値を元に連想配列の中身だけを置換
		$propedit = array_replace($up["user".$getKey], array($mode => $contents));

		if($mode == "status"){
			$propedit = array_replace($up["user".$getKey], array($mode => $contents , "status_date" => date('Y年m月d日 H時i分s秒')));
		}

		// 配列全体を置換
		$compedit = array_replace($up, array("user".$getKey => $propedit));

	// 配列をJSONに変換
	$result = json_encode($compedit, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

	// 既存のJSONファイルをリネームしてバックアップを行う
	copy("./dat/userProperties.json", "./dat/dbBackup/userProperties_backup" . date('YmdHis') . ".json");

	// JSONをファイルに書き出して終了
	file_put_contents("./dat/userProperties.json", $result, LOCK_EX);
}