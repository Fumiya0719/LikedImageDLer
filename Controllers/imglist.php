<?php
namespace Controllers;

// $home = './';
// declare(strict_types = 1);

// require_once($home . "../vendor/autoload.php");

use Abraham\TwitterOAuth\TwitterOAuth;
use Controllers\APIKey;
use \DateTime;

class ImgList extends APIKey {
    private function setCurl($req) {
        $BEARER_TOKEN = 'AAAAAAAAAAAAAAAAAAAAAOpSVQEAAAAAFCRrxMWTLcVmsMU5RF1S8uTJKQs%3D0Qpr1mfNOV9Ls3RvhmzOu2uwocMYpKKOrEtI9OZtpblpo4GmIi';

        // リクエストヘッダの作成
        $header = [
            'Authorization: Bearer ' . $BEARER_TOKEN,
            'Content-Type: application/json',
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $req);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        return $curl;
    }

	public function imgList(array $queue, string $latest_dl = '') {
		/* 
        params of $queue
		id: Twitter ID
        count: 取得するツイート数
        latest_dl: 前回取得した画像以降を取得するかどうか
        object: いいね・ツイート どちらの一覧を取得するか
        using_term: (ツイート一覧を取得する場合のみ) チェックが入っている場合日付、入ってない場合False
        */

        // Connect to TwitterOAuth
        $api_key = new APIKey;
        $connection = new TwitterOAuth($api_key->API_KEY, $api_key->API_KEY_SECRET, $api_key->ACCESS_TOKEN, $api_key->ACCESS_TOKEN_SECRET);

        // Set APIVersion to 2
        $connection->setApiVersion('2');

        /* ------------------------------

        スクリーンネーム(@以降のID)を数値のIDに変換

        ------------------------------ */
        $endPoint = 'https://api.twitter.com/2/users/by/username/';
        $screen_name = $queue['id'];
        $request_url = $endPoint . $screen_name;   
    
        // APIへの問い合わせと数値のtwitter_user_idの取り出し
        $curl = $this->setCurl($request_url);
        $response = curl_exec($curl);
        $res = json_decode($response, true);
        $twitter_user_id = $res['data']['id'];

        /* ------------------------------

            画像ツイート一覧を取得

        ------------------------------ */
        // エンドポイントの判定
        $endPoint = 'https://api.twitter.com/2/users/' . $twitter_user_id;
        switch ($queue['object']) {
            case 'likes':
                $endPoint .= '/liked_tweets';
                break;
            case 'tweets':
                $endPoint .= '/tweets';
                break;
            default:
                $endPoint .= '/liked_tweets';
                break;
        }

        $endPoint = 'https://api.twitter.com/2/users/' . $twitter_user_id . '/liked_tweets';

        // 取得するツイート数
        $tweet_count = $queue['count']; 
        // 1度に取得するツイート数
        $get_tweets_count = 10;
        // 取得したツイートを格納する配列
        $tweet_list = [];
        // 返す配列に挿入したツイートのカウンタ
        $tc = 0;
        // 返す配列
        $tweet_info = [];
        // Pagination Token
        $pagination_token = '';
        while ($tweet_count > 0) {
            $tweet_count -= $get_tweets_count;
            $query = [
                'max_results' => $get_tweets_count,
                'expansions' => 'attachments.media_keys,author_id',
                'tweet.fields' => 'created_at',
                'media.fields' => 'url',
                'user.fields' => 'username',
            ];
    
            if ($queue['object'] === 'tweets' && isset($queue['ed_time'])) $query['end_time'] = $queue['ed_time'];
            
            // Pagination Tokenが存在する場合、クエリに追加
            if ($pagination_token !== '') $query['pagination_token'] = $pagination_token;
    
            $request_url = $endPoint . '?' . http_build_query($query);
    
            // APIへの問い合わせとツイート情報の取り出し
            $curl = $this->setCurl($request_url);
            $response = curl_exec($curl);
            $tweet_list = json_decode($response, true); 

            // ユーザ一覧を[ユーザID] => [ユーザ名]の連想配列に変更
            $tweet_users = [];
            foreach ($tweet_list['includes']['users'] as $u) {
                $tweet_users[$u['id']] = $u['name'];
            }

            // 画像を[メディアキー] => [URL]の連想配列に変更
            $tweet_medias = [];            
            foreach ($tweet_list['includes']['media'] as $m) {
                $tweet_medias[$m['media_key']] = $m['url'];
            }

            foreach ($tweet_list['data'] as $t) {
                // 取得したツイートのIDが前回保存したツイートIDだった場合、キューへの挿入を終了
                if ($latest_dl !== '' && $t['id'] === $latest_dl) break 2;

                $tweet_info[$tc]['post_id'] = $t['id'];
                $tweet_info[$tc]['post_time'] = DateTime::createFromFormat('Y-m-d H:i:s', $t['created_at']);
                $tweet_info[$tc]['user'] = $tweet_users[$t['author_id']];
                $tweet_info[$tc]['text'] = substr($t['text'], 0, -24);
                $tweet_info[$tc]['images'] = [];
                $tweet_info[$tc]['url'] = mb_substr($t['text'], -24);

                // データのメディアキーから画像を挿入
                foreach ($t['attachments']['media_keys'] as $m) {
                    $tweet_info[$tc]['images'][] = $tweet_medias[$m];
                }
                $tc++;
            }

            $all_tweet_list[] = $tweet_list;
            $pagination_token = $tweet_list['meta']['next_token'];
        }
        
        return $tweet_info;
	}
}