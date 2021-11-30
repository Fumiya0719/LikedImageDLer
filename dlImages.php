<?php
// 取得した画像をZipにまとめてダウンロードする
function dlImages(array $images) {

	$zip = new ZipArchive();

	// DLするZipのファイル名
	$dl_file_name = 'images.zip';

	// 保存先ディレクトリ
	$dl_path = 'C:/Users/tanni/Downloads/';

	// Zipを開く
	$st = $zip->open($dl_path . $dl_file_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	// 開けなかった場合の処理
	if (!$st);

	// Zipに画像ファイルを挿入
	foreach ($images as $i) {

			$filepath = $i;
			$ch = curl_init($filepath);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_NOBODY, 0);

			// タイムアウトの値
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		
			$output = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
			if ($status == 200 && mb_strlen($output) != 0){
				// ファイルの取得に成功した場合、Zipにファイルを挿入
				$zip->addFromString(basename($filepath), $output);
			} 

			curl_close($ch);
			sleep(1);

	}

	$zip->close();

	// 作成したZipファイルのダウンロード
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=\"".basename($dl_path . $dl_file_name)."\"");
	ob_end_clean();
	readfile($dl_path. $dl_file_name);
	unlink($dl_path. $dl_file_name);
}