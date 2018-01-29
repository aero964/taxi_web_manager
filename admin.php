<?php
session_start();

/* [!] ↑のコードより上に何も書かないこと！！！！ [!] */


echo <<< EOM
<!DOCTYPE html>
<html>
<head>
    <title>taxi-web-manager</title>
</head>
<body>
<h2>taxi-web-manager</h2>
<p>恐れ入りますが，ブラウザのバックボタンは使用しないで下さい。</p>
<hr>
EOM;

// POSTメソッドで取得したユーザID
$login 			= $_POST["login"];

// POSTメソッドで取得したパスワード
$password		= $_POST["password"];

// GETメソッドでlogoutが送られてきたらログアウト処理
if(isset($_GET["logout"])){
    logOut();
    exit;
}

// ここでデータベース(と言ってもJSON)を読み込み
include("./dataBaseController.php");


// 編集モードのGETメソッドが送られてきた際の処理
if(isset($_GET["edit"]) || isset($_SESSION['login'])){

    $login      = $_SESSION['login'];
    $password   = $_SESSION['password'];


    if($_SESSION['administrator'] == true){
        $_SESSION['getKey'] = $_GET["adminForceExec"];
    }

    if(isset($_GET["Password"])){

        // パスワード変更モード
        if($_SESSION['administrator'] == true){
            execEdit($_SESSION['getKey'], "password", "password");
            echo "<h3>[ID:".$_SESSION['getKey']."]のパスワードを初期パスワード[password]にリセットしました。変更内容をお伝え下さい。</h3><br>";
        }else{
            editPassword();
            exit();
        }

    }else if(isset($_GET["Status"])){

        // 配車受付状態変更モード
        execEdit($_SESSION['getKey'], "status", $_GET["Status"]);
        echo "<h3>配車受付状態を変更しました。</h3><br>";
    }else if(isset($_GET["Other"])){

        // 備考欄変更モード
        echo "備考欄変更モード";
        exit();
    }else if(isset($_GET["userID"])){

        // ユーザID変更モード
        execEdit($_SESSION['getKey'], "userID", $_POST["userID"]);
        echo "<h3>ユーザIDを変更しました。</h3><br>";
    }
}

/* getメソッドに何も指定が無ければ，通常のログイン処理 */

// まずデータベースを読み出す
$userProperties = file_get_contents("./dat/userProperties.json");
$up = json_decode($userProperties, true);
if($up == "NULL"){ die("<h3>データベースの読み出し中にエラーが発生しました。申し訳ありません。</h3><hr><a href='taxiWebManager.php'>戻る</a>"); }

// ログイン名・パスワードが入力されていることを確認
if( empty($login) && empty($password) ){
    print "<h3>ユーザ名・パスワードが入力されていません</h3><hr><a href='taxiWebManager.php'>戻る</a>";
    session_destroy();
    exit();
}

// 入力されたユーザIDをもとに，データベースに登録されているユーザと絞り込み
// 有効なユーザIDであれば，配列の番号を返す。
$getKey = array_search($login, array_column($up, 'userID'));

// DB上のユーザID・パスワードの準備
// ID
$prep_userID 	= $up["user". $getKey]["userID"];

// ハッシュ化されたパスワード
$prep_password  = $up["user". $getKey]["password"];

// パスワードの検証
$password_session = $password;
$password = password_verify($password, $prep_password);

// ユーザ名・パスワードが適切かどうかを確認
if( $login == $prep_userID && $password == $prep_password ){
    
    $_SESSION['login']      = $login;
    $_SESSION['password']   = $password_session;
    $_SESSION['getKey']     = $getKey;
    $_SESSION['name']       = $up["user". $getKey]["userName"];
    $_SESSION['userName']   = $up["user" .$i]["userID"];


    if($login == "Administrator"){

        // Administrator -> 研究室用アカウント -> 管理者権限モードでログイン
        $_SESSION['administrator'] = true;
    	echo "<br><strong style='color: red;'>Administratorアカウント - あなたには管理者権限が与えられています。<br>全ての情報のコントロールが出来るので，取り扱いには十分注意して下さい。</strong><a href='admin.php?logout'>[ログアウト]</a><hr>";
		administratorViewAllUser($up);
    }else{

        // 一般ユーザーモードでログイン
        normalUser($up, $getKey);
    }

}else{

    print "<h3>ユーザ名・パスワードが一致しません。</h3><hr><a href='taxiWebManager.php'>戻る</a>";
    session_destroy();
    exit();
}


/* 呼び出し関数 */

// 情報閲覧 -> 一般ユーザモード
function normalUser($up, $i){

	// 受付状態のON/OFF(true/false)切り替え
    switch ($up["user" .$i]["status"]) {
        case 'false':
            $statusInformation = "<span style='color: red;'>受付を停止中です。</span>";
            $changeStatus      = "true";
            break;
        
        case 'true':
            $statusInformation = "<span style='color: blue;'>受付中です。</span>";
            $changeStatus      = "false";
            break;
    }

    echo "ログイン中のユーザ：<strong>" . $up["user" .$i]["userName"] . "(". $up["user" .$i]["userID"] . ")様</strong><hr>".PHP_EOL;
    echo "<table border='1'>".PHP_EOL;
    echo "<tr><td>パスワード</td><td>(非表示)</td><td><a href='admin.php?edit&Password'>変更する</a></td></tr>".PHP_EOL;
    echo "<tr><td>受付状態</td><td><strong>"        . $statusInformation                    . "</strong></td><td><a href='admin.php?edit&Status=" . $changeStatus . "'>変更する</a></td></tr>".PHP_EOL;
    echo "<tr><td>受付状態変更日時</td><td>"        . $up["user" .$i]["status_date"]        . "</td><td></td></tr>".PHP_EOL;
    //echo "<tr><td>その他備考</td><td>"              . $up["user" .$i]["other"]              . "</td><td><a href='admin.php?edit&Other'>変更する</a></td></tr>".PHP_EOL;
    echo "</table><br><br><a href='admin.php?logout'>ログアウト</a>".PHP_EOL;

}

// 情報閲覧 -> 管理者権限モード
function administratorViewAllUser($up){

/*	if($_SERVER["REMOTE_ADDR"] != "203.0.113.254"){
		die("学外のネットワークからはアクセスできません。");
	}*/

	$uplength = count($up);
	for ($i=1; $i < $uplength - 1; $i++) {

        switch ($up["user" .$i]["status"]) {
            case 'false':
                $statusInformation = "<span style='color: red;'>受付を停止中です。</span>";
                $changeStatus      = "true";
                break;
            
            case 'true':
                $statusInformation = "<span style='color: blue;'>受付中です。</span>";
                $changeStatus      = "false";
                break;
        }
		echo "<table border='1'><form action='admin.php?edit&userID&adminForceExec=". $i ."' method='post'> ".PHP_EOL;
		echo "<tr><th>番号:" . $i . "</th><th>" 		. $up["user" .$i]["userName"] 		. "</th><th>メニュー(⚠取扱注意⚠)</th></tr>".PHP_EOL;
		echo "<tr><td>ユーザID</td><td><input type='text' name='userID' value='" . $up["user" .$i]["userID"]   		. "'></td><td><input type='submit' value='強制変更'></td></tr>".PHP_EOL;
		echo "<tr><td>パスワード</td><td>(非表示)</td><td><a onclick='jump(\"admin.php?edit&Password&adminForceExec=".$i."\")' href='javascript:void(0)'>強制リセット</a></td></tr>".PHP_EOL;
		echo "<tr><td>受付状態</td><td>" 				. $statusInformation ."(". $up["user" .$i]["status"] . ")</td><td><a href='javascript:void(0)' onclick='jump(\"admin.php?edit&Status=".$changeStatus."&adminForceExec=".$i."\")'>強制変更</a></td></tr>".PHP_EOL;
		echo "<tr><td>受付状態変更日時</td><td>"  	    . $up["user" .$i]["status_date"] 	. "</td><td></td></tr>".PHP_EOL;
		//echo "<tr><td>その他備考</td><td>" 				. $up["user" .$i]["other"]	  		. "</td><td>強制変更</td></tr>".PHP_EOL;
		echo "</form></table><br><br>".PHP_EOL;
	}

}

// 編集モード -> パスワード
function editPassword(){
    echo "<form method='post' action='admin.php?edit&Password'>";
    echo "古いパスワード:<input type='password' name='old_password'><br>";
    echo "新しいパスワード:<input type='password' name='new_password'><br>";
    echo "新しいパスワード(もう一度):<input type='password' name='new_password2'><br>";
    echo "<input type='submit'></form>";

    if(isset($_POST["old_password"]) && isset($_POST["new_password"])){
        if($_POST["old_password"] == $_SESSION["password"] && $_POST["new_password"] == $_POST["new_password2"]){
            execEdit($_SESSION['getKey'], "password", $_POST["new_password"]);
            session_destroy();
            echo "<h3>正常にパスワードが登録されました。</h3><hr><a href='taxiWebManager.php'>戻る</a>";
        }else{
            echo("<h3>パスワードが一致しません。</h3><p>パスワードには英数字のみ使用できます。</p><hr><a href='admin.php?'>戻る</a>");
        }
    }else{
    	echo("<p>パスワードには英数字のみ使用できます。</p><hr><a href='admin.php?'>戻る</a>");
    }

}


function logOut(){

    session_destroy(); // セッション情報を削除
    print "<h3>ログアウトしました。</h3><hr><a href='taxiWebManager.php'>戻る</a>";
}


?>

</body>
<script>
function jump(url){
    if(confirm("本当に実行しますか？\n1度実行した場合，基本的にロールバックは出来ません！！")){
        window.location.href = url;
    }else{
        return false;
    }
}
</script>
</html>
