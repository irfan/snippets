<?php
/**
* @Author Irfan Durmus
* @Class vimeoDownloader
* @Description Vimeo video downloader
*
* @Example usage
* $videoID = array(18026253, 12529436);
* $video = new vimeoDownloader;
* $video = $video->download($videoID);
* 
* OR
*
* $video = new vimeoDownloader;
* $video = $video->download(18026253);
*
* And just
* php vimeo-downloader.php
* 
* The output will be look like that;
*
* 18026253 -> download started from vimeo, please wait...
* 18026253 -> Video saved with id name! 
* 12529436 -> download started from vimeo, please wait...
* 12529436 -> Video saved with id name! 
*
* @TODO
* - Detect file extensions to add filename
* - Check directory is writeable
*/

class vimeoDownloader
{
	private $url;					// xml url
	private $durl;					// downloading url
	private $agent;					// user agent
	private $id;					// video id(s)
	private $xml;					// xml file
	private $attributes = array();	// xml attributes
	
	private $messages = array();	// printing messages
	private $cl;					// curl resource
	private $types;					// curl request types [xml | video]
	private $activeID;				// downloading video id
	
	private $path;					// video saving directory
	private $videoFile;				// downloaded video file full path on disk
	
	
	function __construct()
	{
		$this->path = realpath(__DIR__) . DIRECTORY_SEPARATOR;
		
		$this->messages['started'] = " -> download started from vimeo, please wait...\n";
		$this->messages['finished'] = " -> Video saved with id name! \n";
		
		$this->agent = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; tr; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13";
		$this->url = "http://www.vimeo.com/moogaloop/load/clip:";
		$this->durl = "http://www.vimeo.com/moogaloop/play/clip:";
		
		$this->types[0] = 'xml';
		$this->types[1] = 'video';
	}
	
	
	public function __set($name, $value)
	{
		$this->$name = $value;
	}
	
	
	public function __get($name)
	{
		return $this->$name;
	}
	
	
	private function openConn($url, $type)
	{
		$this->cl = curl_init($url);
		curl_setopt($this->cl, CURLOPT_URL, $url);
		curl_setopt($this->cl, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($this->cl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->cl, CURLOPT_RETURNTRANSFER, 1);
		
		if ($type == 'video') {
			$filePath = $this->path . $this->attributes['caption'];
			$this->videoFile = fopen($filePath, 'w');
			curl_setopt($this->cl, CURLOPT_FILE, $this->videoFile);
		}
		
		return curl_exec($this->cl);
		
	}
	
	private function closeConn()
	{
		curl_close($this->cl);
		return $this;
	}
	
	
	private function getXml()
	{
		$url = $this->url . $this->activeID;
		$this->xml = $this->openConn($url, $this->types[0]);
		$this->closeConn();
		
		return $this;
	}
	
	
	private function saveVideo()
	{
		echo $this->activeID . $this->messages['started'];
		
		$attr = $this->attributes;
		$url = $this->durl . $this->activeID . DIRECTORY_SEPARATOR . $attr['signature'][0] . DIRECTORY_SEPARATOR . $attr['expires'][0] . DIRECTORY_SEPARATOR;
		$type = $this->types[1];
		
		$this->openConn($url, $type);
		$info = curl_getinfo($this->cl);
		$this->closeConn();
		fclose($this->videoFile);
		
		if ($info['http_code'] == '200') {
			echo $this->activeID . $this->messages['finished'];
		}
		
		return $this;
				
	}
	
	
	private function parseXml()
	{
		$xmlData = $this->xml;
		
		$parser = simplexml_load_string($xmlData);
		$this->attributes['signature'] = $parser->request_signature;
		$this->attributes['expires'] = $parser->request_signature_expires;
		$this->attributes['caption'] = $this->activeID;
		
		return $this;
	}
	
	
	public function download($vid)
	{
		$this->id = $vid;
		$type = gettype($vid);
		
		switch ($type) {
			case 'array':
				foreach ($vid as $id) {
					$this->activeID = $id;
					$this->getXml()->parseXml()->saveVideo();
				}
				break;
			
			default:
				$this->activeID = $vid;
				$this->getXml()->parseXml()->saveVideo();
				break;
		}
	return true;
	
	}
}
