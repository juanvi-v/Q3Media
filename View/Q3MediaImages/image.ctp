<?php
	$this->layout='Q3Media.image';
	ob_clean();

	//$image = $this->Q3Image->getResized($id, $mime, $options);
	//debug($mime);
	//header("Content-Type: $mime");

	header("Expires: ".gmdate("D, d M Y H:i:s", time()+315360000)." GMT");
	header("Cache-Control: max-age=315360000");

	fpassthru($image_content);
	fclose($image_content);
