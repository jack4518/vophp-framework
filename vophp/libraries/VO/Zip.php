<?php
/**
 * 定义 VO_Zip ZIP压缩类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-10-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Zip extends VO_Object{
	
	/**
	 * 压缩包内的二进制数据内容
	 * @var binary
	 */
	var $zipContent	= '';
	var $directory 	= '';
	var $entries 	= 0;
	var $file_num 	= 0;
	var $offset		= 0;
	
	/**
	 * 构造器
	 */
	public function __construct(){

	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Zip
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Zip ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 增加一个空目录
	 *
	 * @param	string|array	$folder	目录名称
	 * @return	void
	 */
	public function createFolder($folder)
	{
		if(!is_array($folder)){
			$folder = (array) $folder;
		}
		foreach($folder as $dir){
			if( substr($dir, strlen($dir)-1) != '/' ){
				$dir .= '/';
			}
			$this->_addFolder($dir);
		}
	}

	/**
	 * 增加一个目录
	 *
	 * @param	string	$folder 目录名称
	 * @return	void
	 */
	private function _addFolder($folder)
	{
		$folder = str_replace("\\", "/", $folder);

		$this->zipContent .=
			"\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00"
			.pack('V', 0) // crc32
			.pack('V', 0) // compressed filesize
			.pack('V', 0) // uncompressed filesize
			.pack('v', strlen($folder)) // length of pathname
			.pack('v', 0) // extra field length
			.$folder
			// below is "data descriptor" segment
			.pack('V', 0) // crc32
			.pack('V', 0) // compressed filesize
			.pack('V', 0);// uncompressed filesize

		$this->directory .=
			"\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00"
			.pack('V',0) // crc32
			.pack('V',0) // compressed filesize
			.pack('V',0) // uncompressed filesize
			.pack('v', strlen($folder)) // length of pathname
			.pack('v', 0) // extra field length
			.pack('v', 0) // file comment length
			.pack('v', 0) // disk number start
			.pack('v', 0) // internal file attributes
			.pack('V', 16) // external file attributes - 'directory' bit set
			.pack('V', $this->offset) // relative offset of local header
			.$folder;

		$this->offset = strlen($this->zipContent);
		$this->entries++;
	}

	/**
	 * 向压缩包中添加一个或多个文件
	 *
	 * @param	string|array $filename  文件名称
	 * @param	string $data
	 * @return	void
	 */	
	public function createFile($filename, $data = NULL)
	{
		if (is_array($filename)){
			foreach ($filename as $p => $data){
				$this->_addData($p, $data);
			}
		}else{
			$this->_addData($filename, $data);
		}
	}

	/**
	 * 向压缩文件中添加文件
	 *
	 * @param	string	文件名(可包含路径)
	 * @param	string	文件的内容
	 * @return	void
	 */	
	private function _addData($filepath, $data)
	{
		$filepath = str_replace("\\", "/", $filepath);

		$filesize = strlen($data);
		$crc32  = crc32($data);

		$data = gzcompress($data);
		$data = substr($data, 2, -4);
		$compressedSize = strlen($data);

		$this->zipContent .=
			"\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00\x00\x00\x00\x00"
			.pack('V', $crc32)
			.pack('V', $compressedSize)
			.pack('V', $filesize)
			.pack('v', strlen($filepath)) // 文件名长度
			.pack('v', 0) // extra field length
			.$filepath
			.$data;// 文件内容

		$this->directory .=
			"\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00\x00\x00\x00\x00"
			.pack('V', $crc32)
			.pack('V', $compressedSize)
			.pack('V', $filesize)
			.pack('v', strlen($filepath)) // length of filename
			.pack('v', 0) // extra field length
			.pack('v', 0) // file comment length
			.pack('v', 0) // disk number start
			.pack('v', 0) // internal file attributes
			.pack('V', 32) // external file attributes - 'archive' bit set
			.pack('V', $this->offset) // relative offset of local header
			.$filepath;

		$this->offset = strlen($this->zipContent);
		$this->entries++;
		$this->file_num++;
	}

	/**
	 * 读取指定文件，并且将文件加入压缩包
	 * @param string $file 文件名称
	 * @param bool $keepPath  是否保持文件的全路径
	 * @return	bool
	 */	
	public function addFile($file, $keepPath = false)
	{
		if( !file_exists($file) ){
			return false;
		}
		$content = VO_Filesystem_File::read($file);
		if( false !== ($content) ){
			$name = str_replace("\\", "/", $file);
			if($keepPath === false){
				$name = basename($file);
			}
			$this->createFile($name, $content);
			return true;
		}else{
			$this->triggerError('无法打开文件："' . $file . '"');
			exit;
		}
	}
	
	/**
	 * 向压缩包里添加一个目录(包括其下的所有文件及子目录)
	 * @param	string	$path 目录的路径
	 * @return	bool
	 */	
	public function addFolder($path)
	{	
		if(!VO_Filesystem_Folder::exists($path)){
			$this->triggerError('目录:"' . $path . '"不存在');
			exit;
		}
		$fp = @opendir($path);
		if($fp){
			while(false !== ($file = readdir($fp))){
				$fullpath = $path . DS . $file;
				if( is_dir($fullpath) && (substr($file, 0, 1) != '.') && ($file != '..') ){
					$this->addFolder( $fullpath . DS );
				}elseif( substr($file, 0, 1) != "."){
					$this->addFile( $fullpath );
				}
			}
			return true;
		}else{
			$this->triggerError('打开目录:"' . $path . '"失败');
			exit;
		}
	}

	/**
	 * 获取zip压缩数据
	 *
	 * @return	binary 二进制字符串
	 */	
	public function getZipContent()
	{
		if ($this->entries == 0){
			return FALSE;
		}

		$zip_data = $this->zipContent;
		$zip_data .= $this->directory . "\x50\x4b\x05\x06\x00\x00\x00\x00";
		$zip_data .= pack('v', $this->entries);// total # of entries "on this disk"
		$zip_data .= pack('v', $this->entries);// total # of entries overall
		$zip_data .= pack('V', strlen($this->directory));// size of central dir
		$zip_data .= pack('V', strlen($this->zipContent));// offset to start of central dir
		$zip_data .= "\x00\x00";// .zip file comment length

		return $zip_data;
	}

	/**
	 * 保存压缩文件并存储到指定的目录下
	 *
	 * @param	string	$file 文件名(包括完整的路径,如果文件不存在，则创建一下文件)
	 * @return	bool
	 */	
	public function compress($file)
	{
		$data = $this->getZipContent();
		return VO_Filesystem_File::write($file, $data);
	}
	/**
	 * 将打包好的文件生成后让用户下载
	 *
	 * @param	string	the file name
	 * @return	bool
	 */
	public function download($filename = 'file.zip')
	{
		if ( substr($filename, strlen($filename)-3) != 'zip'){
			$filename .= '.zip';
		}
		header('Pragma: no-cache');
		header("Content-Type: application/zip;name=\"$filename\"");
		header("Content-disposition: attachment;filename=$filename");
		var_dump( $this->getZipContent() );
	}

	/**
	 * 清除压缩数据缓存
	 * @return	void
	 */		
	public function clear()
	{
		$this->zipContent	= '';
		$this->directory	= '';
		$this->entries		= 0;
		$this->file_num		= 0;
		$this->offset		= 0;
	}
	
	/**
	 * 解压缩文件
	 * @param string $zipFile  压缩文件
	 * @param string $to  目标目录
	 * @param array $index
	 * @return string|number|unknown
	 */
	function extract( $zipFile, $to, $index = Array(-1) ){
	   $ok = 0; 
	   $zip = @fopen($zipFile, 'rb');
	   if(!$zip){
			return(-1);
	   }
	   $cdir = $this->ReadCentralDir($zip, $zipFile);
	   $pos_entry = $cdir['offset'];
	 
	   if(!is_array($index)){ 
			$index = array($index);
	   }
	   for($i=0; @$index[$i];$i++){
	   		if(intval($index[$i])!=$index[$i]||$index[$i]>$cdir['entries'])
			return(-1);
	   }
	   for ($i=0; $i<$cdir['entries']; $i++){
			@fseek($zip, $pos_entry);
			$header = $this->ReadCentralFileHeaders($zip);
			$header['index'] = $i; $pos_entry = ftell($zip);
			@rewind($zip); fseek($zip, $header['offset']);
			if(in_array("-1",$index)||in_array($i,$index)){
				$stat[$header['filename']]=$this->ExtractFile($header, $to, $zip);
			}
	   }
	   fclose($zip);
	   return $stat;
	 }
 
	 /**
	  * 读取整个压缩包头信息
	  * @param $zip  使用fopen打开的二进制文件内容
	  * @reutrn array
	  */
	function ReadFileHeader($zip){
		$binary_data = fread($zip, 30);
		$data = unpack('vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $binary_data);
		 
		$header['filename'] = fread($zip, $data['filename_len']);
		if ($data['extra_len'] != 0) {
			$header['extra'] = fread($zip, $data['extra_len']);
		}else{ 
			$header['extra'] = ''; 
		}
		 
		$header['compression'] = $data['compression'];$header['size'] = $data['size'];
		$header['compressed_size'] = $data['compressed_size'];
		$header['crc'] = $data['crc']; $header['flag'] = $data['flag'];
		$header['mdate'] = $data['mdate'];$header['mtime'] = $data['mtime'];
		 
		if ($header['mdate'] && $header['mtime']){
			$hour=($header['mtime']&0xF800)>>11;$minute=($header['mtime']&0x07E0)>>5;
			$seconde=($header['mtime']&0x001F)*2;$year=(($header['mdate']&0xFE00)>>9)+1980;
			$month=($header['mdate']&0x01E0)>>5;$day=$header['mdate']&0x001F;
			$header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
		}else{
			$header['mtime'] = time();
		}
		 
		$header['stored_filename'] = $header['filename'];
		$header['status'] = "ok";
		return $header;
	}
 
	/**
	 * 读取压缩包里某个文件的压缩头信息
	 * @param $zip
	 */
 	function ReadCentralFileHeaders($zip){
		$binary_data = fread($zip, 46);
		$header = unpack('vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $binary_data);
		 
		if ($header['filename_len'] != 0){
		 $header['filename'] = fread($zip,$header['filename_len']);
		}else{
			$header['filename'] = '';
		}
		 
		if ($header['extra_len'] != 0){
		 	$header['extra'] = fread($zip, $header['extra_len']);
		}else{
			$header['extra'] = '';
		}
		 
		if ($header['comment_len'] != 0){
		 	$header['comment'] = fread($zip, $header['comment_len']);
		}else{
			$header['comment'] = '';
		}
		 
		if ($header['mdate'] && $header['mtime']){
			$hour = ($header['mtime'] & 0xF800) >> 11;
			$minute = ($header['mtime'] & 0x07E0) >> 5;
			$seconde = ($header['mtime'] & 0x001F)*2;
			$year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
			$month = ($header['mdate'] & 0x01E0) >> 5;
			$day = $header['mdate'] & 0x001F;
			$header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
		}else{
			$header['mtime'] = time();
		}
		$header['stored_filename'] = $header['filename'];
		$header['status'] = 'ok';
		if (substr($header['filename'], -1) == '/'){
			$header['external'] = 0x41FF0010;
		}
		return $header;
	}
 
	/**
	 * 读取压缩包里的目录头信息
	 * @param $zip
	 * @param $zip_name
	 * @return array
	 */
	function ReadCentralDir($zip,$zip_name){
		$size = filesize($zip_name);
	 
		if ($size < 277){
			$maximum_size = $size;
		}else{
			$maximum_size=277;
		}
		 
		@fseek($zip, $size-$maximum_size);
		$pos = ftell($zip); $bytes = 0x00000000;
		 
		while ($pos < $size){
			$byte = @fread($zip, 1); $bytes=($bytes << 8) | ord($byte);
			if ($bytes == 0x504b0506 or $bytes == 0x2e706870504b0506){ 
				$pos++;
				break;
			} 
			$pos++;
		}
		 
		$fdata=fread($zip,18);
		 
		$data=@unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size',$fdata);
		 
		if ($data['comment_size'] != 0){
			$centd['comment'] = fread($zip, $data['comment_size']);
		}else{
			$centd['comment'] = ''; $centd['entries'] = $data['entries'];
		}
		$centd['disk_entries'] = $data['disk_entries'];
		$centd['offset'] = $data['offset'];$centd['disk_start'] = $data['disk_start'];
		$centd['size'] = $data['size'];  $centd['disk'] = $data['disk'];
		return $centd;
	}
 
	/**
	 * 解压文件
	 * @param $header
	 * @param $to
	 * @param $zip
	 */
	function ExtractFile($header,$to,$zip){
		$header = $this->readfileheader($zip);
 
		if(substr($to,-1)!="/"){
			$to.="/";
		}
		if($to=='./'){
			$to = '';
		}
		$pth = explode("/",$to.$header['filename']);
		$mydir = '';
		for($i=0;$i<count($pth)-1;$i++){
			if(!$pth[$i]) continue;
			$mydir .= $pth[$i]."/";
			//解压目录
			if((!is_dir($mydir) && @mkdir($mydir,0777)) || (($mydir==$to.$header['filename'] || ($mydir==$to && $this->total_folders==0)) && is_dir($mydir)) ){
				@chmod($mydir,0777);
				$this->total_folders ++;
				//echo "<input name='dfile[]' type='checkbox' value='$mydir' checked> <a href='$mydir' target='_blank'>目录: $mydir</a><br>";
			}
		}
 
		if(strrchr($header['filename'],'/')=='/'){
			return;
		}
 
		if (!(@$header['external']==0x41FF0010)&&!(@$header['external']==16)){
			if ($header['compression']==0){
				$fp = @fopen($to.$header['filename'], 'wb');
				if(!$fp){
					return(-1);
				}
				$size = $header['compressed_size'];
			 
				while ($size != 0){
					$read_size = ($size < 2048 ? $size : 2048);
					$buffer = fread($zip, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size -= $read_size;
				}
				fclose($fp);
				touch($to.$header['filename'], $header['mtime']);
			}else{
				$fp = @fopen($to.$header['filename'].'.gz','wb');
				if(!$fp){
					return(-1);
				}
				$binary_data = pack('va1a1Va1a1', 0x8b1f, Chr($header['compression']),
				Chr(0x00), time(), Chr(0x00), Chr(3));
				 
				fwrite($fp, $binary_data, 10);
				$size = $header['compressed_size'];
			 
				while ($size != 0){
					$read_size = ($size < 1024 ? $size : 1024);
					$buffer = fread($zip, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size -= $read_size;
				}
			 
				$binary_data = pack('VV', $header['crc'], $header['size']);
				fwrite($fp, $binary_data,8); fclose($fp);
			 
				$gzp = @gzopen($to.$header['filename'].'.gz','rb') or die("Cette archive est compress閑");
				if(!$gzp){
					return(-2);
				}
				$fp = @fopen($to.$header['filename'],'wb');
				if(!$fp){
					return(-1);
				}
				$size = $header['size'];
			 
				while ($size != 0){
					$read_size = ($size < 2048 ? $size : 2048);
					$buffer = gzread($gzp, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size -= $read_size;
				}
				fclose($fp); gzclose($gzp);
				 
				touch($to.$header['filename'], $header['mtime']);
				@unlink($to.$header['filename'].'.gz');
			}
		}
 
		$this->total_files ++;

		return true;
	}

}
