<?php
class Q3ImageComponent extends Component
{

	public function imageUpload($file=array(),$id=null,$subfolder=''){

		if(!empty($file['tmp_name'])&& !empty($id)){
			$folder=Q3MEDIA_IMG_UPLOAD_FOLDER;
			if(!empty($subfolder)){
				$folder.=$subfolder.DS;
			}
			move_uploaded_file($file['tmp_name'], $folder.$id);
		}
		else{
			return false;
		}
	}

	public function getImage($id,$subfolder=''){
		$folder=Q3MEDIA_IMG_UPLOAD_FOLDER;
		if(!empty($subfolder)){
			$folder.=$subfolder.DS;
		}
		if(file_exists($folder.$id)){
			$file=$id;
		}
		else{
			$file=null;
		}
		return $file;
	}


	//function getResized($id, &$mime, $imgFolder, $newWidth=false, $newHeight=false, $bgcolor="000000", $resample=true, $cache=false, $cacheFolder=false, $cacheClear=false, $tempFolder=false, $crop=true, $crop_direction='N', $enlarge=true,$resize=false,$watermark=false)
	function getResized($id, &$mime, $options=array()){

		//$imgFolder,

		$default_options=array(
				'newWidth'=>false,
				'newHeight'=>false,
				'bgcolor'=>'FFFFFF',
				'resample'=>true,
				'subFolder'=>'',
				'imgFolder'=>Q3MEDIA_IMG_UPLOAD_FOLDER,
				'tempFolder'=>Q3MEDIA_IMG_TEMP_FOLDER,
				'cacheFolder'=>Q3MEDIA_IMG_CACHE_FOLDER,
				'cache'=>Q3MEDIA_IMG_CACHE,
				'cacheClear'=>false,
				'crop'=>true,
				'crop_direction'=>'N',
				'enlarge'=>true,
				'resize'=>false,
				'watermark'=>false
		);

		$actual_options=array_merge($default_options,$options);
		extract($actual_options);

		$cacheClear=true;//?¿

		if(!empty($subFolder)){
			$imgFolder.=$subFolder.DS;
			$tempFolder.=$subFolder.DS;
			$cacheFolder.=$subFolder.DS;
		}

		$img = $imgFolder .$id;
		list($oldWidth, $oldHeight, $type) = getimagesize($img);
		$ext = $this->image_type_to_extension($type);
		$mime = image_type_to_mime_type($type);

		if ($cache AND is_writeable($cacheFolder)){
			$extra=$crop_direction;
			if($enlarge){
				$extra.='_e';
			}
			$dest = $cacheFolder . $id . '_' . $newWidth . 'x' . $newHeight.'_'.$extra;
		}
		elseif (is_writeable($tempFolder)){
			$dest = $tempFolder . $id;
		}
		else{
			debug("You must set either a cache folder or temporal folder for image processing. And the folder has to be writable.");
			if (strlen($cacheFolder)){
				debug("Cache Folder \"".$cacheFolder."\" has permissions ".substr(sprintf('%o', fileperms($cacheFolder)), -4));
				debug("Please run \"chmod 777 $cacheFolder\"");
			}
			if (strlen($tempFolder)){
				debug("Temp Folder: ".$tempFolder." Permissions:".substr(sprintf('%o', fileperms($tempFolder)), -4));
				debug("Run \"chmod 777 $tempFolder\"");
			}
			exit();
		}

		if ($newWidth OR $newHeight)
		{
			if($cacheClear && file_exists($dest))
			{
				unlink($dest);
			}

			if($cache AND file_exists($dest))
			{
				$i = fopen($dest, 'rb');
			}
			else
			{

				if(!$enlarge && !$resize){
					if($newWidth>$oldWidth){
						$newWidth=$oldWidth;
					}
					if($newHeight>$oldHeight){
						$newHeight=$oldHeight;
					}
				}
				if($newHeight==0){
					$newHeight=$newWidth*$oldHeight/$oldWidth;
				}

				if($newWidth==0){
					$newWidth=$newHeight*$oldWidth/$oldHeight;
				}

				if($crop){
					// Horizontal cropping
					if($newWidth/$newHeight<$oldWidth/$oldHeight){
						$delta_x=$oldWidth-($oldHeight*$newWidth)/$newHeight;
						$delta_y=0;
						$start_y=0;
						// Intermediate image size (to obtain advanced cropping)
						$midWidth = round(($oldWidth*$newHeight)/$oldHeight);
						switch($crop_direction){
							case 'W':
							case 'NW':
							case 'SW': $start_x=0;
							break;
							case 'E':
							case 'NE':
							case 'SE': $start_x=$delta_x;
							break;
							case 'C':
							case 'S':
							case 'N':$start_x=$delta_x/2;
							break;

							default:
								$start_x = explode("-",$crop_direction);
								$start_x = ($start_x[0]*$delta_x)/($midWidth-$newWidth);
								// Fix crop if out of bounds.
								if(($start_x+$newWidth)>$midWidth) $start_x -= (($start_x+$newWidth)-$midWidth);
								break;
						}

					}
					// Vertical cropping
					else{
						$delta_x=0;
						$delta_y=$oldHeight-($oldWidth*$newHeight)/$newWidth;
						$start_x=0;
						// Intermediate image size (to obtain advanced cropping)
						$midHeight = round(($oldHeight*$newWidth)/$oldWidth);
						switch($crop_direction){
							case 'N':
							case 'NE':
							case 'NW': $start_y=0;
							break;
							case 'S':
							case 'SE':
							case 'SW': $start_y=$delta_y;
							break;
							case 'C':
							case 'E':
							case 'W':$start_y=$delta_y/2;
							break;
							default:

								$start_y = explode("-",$crop_direction);
								$start_y = ($start_y[1]*$delta_y)/($midHeight-$newHeight);
								// Fix crop if out of bounds.
								if(($start_y+$newHeight)>$midHeight) $start_y -= (($start_y+$newHeight)-$midHeight);
								break;
						}

					}
				}
				else{
					if(!$resize && ($newWidth > $oldWidth) && ($newHeight > $oldHeight)){

						$applyWidth = $oldWidth;
						$applyHeight = $oldHeight;

					}
					else{
						if(($newWidth/$newHeight) < ($oldWidth/$oldHeight)){
							$applyHeight = $newWidth*$oldHeight/$oldWidth;
							$applyWidth = $newWidth;
						}
						else{
							$applyWidth = $newHeight*$oldWidth/$oldHeight;
							$applyHeight = $newHeight;
						}
					}
				}

				switch($ext)
				{
					case 'gif' :
						$oldImage = imagecreatefromgif($img);
						$newImage = imagecreate($newWidth, $newHeight);
						break;
					case 'png' :
						$oldImage = imagecreatefrompng($img);
						$newImage = imagecreatetruecolor($newWidth, $newHeight);
						break;
					case 'jpg' :
						$oldImage = imagecreatefromjpeg($img);
						$newImage = imagecreatetruecolor($newWidth, $newHeight);
						break;
					case 'jpeg' :
						$oldImage = imagecreatefromjpeg($img);
						$newImage = imagecreatetruecolor($newWidth, $newHeight);
						break;
					default :
						return false;
						break;
				}

				sscanf($bgcolor, "%2x%2x%2x", $red, $green, $blue);
				$newColor = ImageColorAllocate($newImage, $red, $green, $blue);
				imagefill($newImage,0,0,$newColor);
				/*
				 debug($newWidth.' - '.$newHeigh);
				debug($applyWidth.' - '.$applyHeight);
				debug($oldWidth.' - '.$oldHeight);
				die();
				*/



				if ($resample==true)
				{
					if($crop){
						ImageCopyResampled($newImage,$oldImage,0,0,$start_x,$start_y,$newWidth,$newHeight,$oldWidth-$delta_x,$oldHeight-$delta_y);
					}
					else{
						ImageCopyResampled($newImage, $oldImage, ($newWidth-$applyWidth)/2, ($newHeight-$applyHeight)/2, 0, 0, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
					}
				}
				else
				{
					if($crop){
						ImageCopyResized($newImage,$oldImage,0,0,$start_x,$start_y,$newWidth,$newHeight,$oldWidth-$delta_x,$oldHeight-$delta_y);
					}
					else{
						ImageCopyResampled($newImage, $oldImage, ($newWidth-$applyWidth)/2, ($newHeight-$applyHeight)/2, 0, 0, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
					}
				}


				if($watermark && file_exists($watermark)){


					list($wmWidth, $wmHeight, $wmtype) = getimagesize($watermark);
					$wmImage = imagecreatefrompng($watermark);
					//imagealphablending($wmImage, TRUE);
					//					debug($wmWidth.'x'.$wmHeight);
					//					debug($applyWidth.'x'.$applyHeight);
					//					debug('0'.'x'.($applyHeight-$wmHeight).'x'.'0'.'x'.'0'.'x'.$wmWidth.'x'.$wmHeight.'x'.$applyWidth.'x'.$applyHeight);

					imagealphablending($newImage, TRUE);
					$factor=($newWidth/$wmWidth);
					//					debug('0'.'x'.($applyHeight-$wm_real_height).'x'.'0'.'x'.'0'.'x'.$wmWidth.'x'.$wmHeight.'x'.$applyWidth.'x'.$wm_real_height);
					//					debug($wm_real_height);
					//					die();
					//ImageCopyResized($newImage,$wmImage,0,($applyHeight-$wm_real_height),0,0,$wmWidth,$wmHeight,$applyWidth,$wm_real_height);
					ImageCopyResized($newImage,$wmImage,0,($newHeight-($newHeight-$applyHeight)/2-$factor*$wmHeight),0,0,$factor*$wmWidth,$factor*$wmHeight,$wmWidth,$wmHeight);


					//				$newImage=$wmImage;
					//			imagealphablending($newImage, TRUE);
				}

				//			die();

				switch($ext)
				{
					case 'gif' :
						imagegif($newImage, $dest);
						break;
					case 'png' :
						imagepng($newImage, $dest);
						break;
					case 'jpg' :
						imagejpeg($newImage, $dest, '90');
						break;
					case 'jpeg' :
						imagejpeg($newImage, $dest);
						break;
					default :
						return false;
						break;
				}

				imagedestroy($newImage);
				imagedestroy($oldImage);
				$i = fopen($dest, 'rb');
			}

		}
		else
		{
			$i = fopen($img, 'rb');
		}

		return $i;
	}

	/**
	 * Returns dimensions on given image file
	 * @param string $id
	 * @param string $subfolder
	 * @return array(int, int)
	 */
	function getImageSize($id, $subfolder=null){

		$image=$this->getImage($id,$subfolder);
		if(!empty($image)){
			$folder=Q3MEDIA_IMG_UPLOAD_FOLDER;
			if(!empty($subfolder)){
				$folder.=$subfolder.DS;
			}
			list($imgWidth, $imgHeight, $type) = getimagesize($folder.$image);
		}
		else{
			$imgWidth=null;
			$imgHeight=null;
		}

		return array($imgWidth,$imgHeight);
	}


	function image_type_to_extension($imagetype)
	{
		if(empty($imagetype)) return false;
		switch($imagetype)
		{
			case IMAGETYPE_GIF    : return 'gif';
			case IMAGETYPE_JPEG    : return 'jpg';
			case IMAGETYPE_PNG    : return 'png';
			case IMAGETYPE_SWF    : return 'swf';
			case IMAGETYPE_PSD    : return 'psd';
			case IMAGETYPE_BMP    : return 'bmp';
			case IMAGETYPE_TIFF_II : return 'tiff';
			case IMAGETYPE_TIFF_MM : return 'tiff';
			case IMAGETYPE_JPC    : return 'jpc';
			case IMAGETYPE_JP2    : return 'jp2';
			case IMAGETYPE_JPX    : return 'jpf';
			case IMAGETYPE_JB2    : return 'jb2';
			case IMAGETYPE_SWC    : return 'swc';
			case IMAGETYPE_IFF    : return 'aiff';
			case IMAGETYPE_WBMP    : return 'wbmp';
			case IMAGETYPE_XBM    : return 'xbm';
			default                : return false;
		}
	}
}
