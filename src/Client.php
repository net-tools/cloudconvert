<?php

// namespace
namespace Nettools\CloudConvert;




class Client
{
	// url api
	const CC_API = 'https://api.cloudconvert.com/';
	
		
	
	// prepare request
	protected function _createRequest($params)
	{
		$ret = array();
		foreach ( $params as $k => $v )
			$ret[] = strtolower($k) . '=' . urlencode($v);
			
		return implode('&', $ret);
	}
		
	
	// user api key
	public $apikey = NULL;
		
	
	// constructor
	public function __construct($apikey)
	{
		$this->apikey = $apikey;
	}
	
		
	// list conversions
	public function listConversions()
	{
//		$list = file_get_contents('https://api.cloudconvert.com/processes?apikey=' . App_Frmk_Application::$appRegistry->fact_cloudconvert_apikey);
		return $this->execute('GET', self::CC_API . 'processes', array('apikey'=>$this->apikey));
	}
	
	
	// delete conversion
	public function deleteConversion($url)
	{
		return $this->execute('DELETE', 'https:' . $url);
	}
	
	
	// delete all conversions
	public function deleteConversions()
	{
		$list = $this->listConversions();
		foreach ( $list as $conv )	
			$this->deleteConversion($conv['url']);
			
		return true;
	}
	
	
	// convert a file that'll we downloaded by cloudconvert servers to be processed
	public function convertDownload($inputformat, $outputformat, $fileurl, $params = array())
	{
		$params['apikey'] = $this->apikey;
		$params['inputformat'] = $inputformat;
		$params['outputformat'] = $outputformat;
		$params['input'] = 'download';
		$params['file'] = $fileurl;
		$params['wait'] = true;
		
		return $this->execute('GET', self::CC_API . 'convert', $params);
	}
	
	
	// convert some data (to be uploaded to CloudConvert)
	public function convertUploadData($inputformat, $outputformat, $data, $params = array())
	{
		// create a temporary file with the correct extension
		$fname = tempnam('/tmp', 'cc') . ".$inputformat";
		$f = fopen($fname, 'w');
		fwrite($f, $data);
		fclose($f);
		
		try
		{
			$ret = $this->convertUpload($inputformat, $outputformat, $fname, $params);
			unlink($fname);
			return $ret;
		}
		catch (CloudConvertException $e)
		{
			unlink($fname);
			throw $e;
		}
	}
	
	
	// convert a file that'll be uploaded along with the request
	public function convertUpload($inputformat, $outputformat, $file, $params = array())
	{
		$params['apikey'] = $this->apikey;
		$params['inputformat'] = $inputformat;
		$params['outputformat'] = $outputformat;
		$params['input'] = 'upload';
		$params['file'] = new \CURLFile($file);
		$params['wait'] = true;
		
		return $this->execute('POST', self::CC_API . 'convert', $params);
	}
	
	
	// execute the request
	public function execute($verb, $url, $params = array())
	{
		// init curl
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		// type of request (GET/POST/DELETE)
		switch ( $verb )
		{
			// GET : querystring is on url
			case 'GET':
				curl_setopt($curl, CURLOPT_HTTPGET, true);
				curl_setopt($curl, CURLOPT_URL, $url . '?' . $this->_createRequest($params));
				break;
				
			// POST : request if in the body
			case 'POST':
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
				
			// default case : request in the url
			default:
				curl_setopt($curl, CURLOPT_URL, $url . '?' . $this->_createRequest($params));
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $verb);
				break;
		}
		

		// exec curl
		$ret = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		// if process is KO, the error is written in JSON
		if ( $http_code != 200 )
		{
			// decode the json
			$json = json_decode($ret, true);
			if ( !is_null($json) )
				throw new CloudConvertException('Exception CloudConvert : \'' . $json['error'] . ' (code ' . $json['code'] . ')\'');
			else
				throw new CloudConvertException("Exception CloudConvert HTTP Code $http_code '$ret'");
		}
		
		
		// request OK, see if we have JSON returned
		if ( strlen($ret) )
		{
			$r = trim($ret);
			
			// if json detected
			if ( 
					( (substr($r, 0, 1) == '{') && (substr($r, -1) == '}') )
					||
					( (substr($r, 0, 1) == '[') && (substr($r, -1) == ']') )
				)
			
			{
				$json = json_decode($r, true);
				if ( !is_null($json) )
					return $json;
				else
					throw new CloudConvertException('Json CloudConvert can not be processed : ' . $r);
			}
			
			// if no json detected, return as is (this is the case for an inline conversion)
			else
				return $ret;			
		}
		else
			return '';
	}
}


?>