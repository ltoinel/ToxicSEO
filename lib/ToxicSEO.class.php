<?php

// Load our autoloader
require_once __DIR__.'/../vendor/autoload.php';

/**
 * Main Toxic SEO Class
 */
class ToxicSEO
{
	private $dbh;
	private $conf;

	/**
	 * Main constructor
	 */
	function ToxicSEO($conf)
	{
		$this->conf = $conf;
	}

	/** 
	 * Retourne une connexion vers la base de données 
	 */
	function getDbConnection()
	{
		// If the connection exists we reuse it
		if ($this->dbh != null) {
			return  $this->dbh;
		}

		// Create a new connection to the database
		try {
			$this->dbh = new PDO("mysql:host=" . $this->conf->hostname . ";dbname=" . $this->conf->database, $this->conf->username, $this->conf->password);
		} catch (PDOException $e) {
			throw $e;
		}

		return $this->dbh;
	}

	/***
	 * Save or update the link in the database.
	 */
	function saveOrUpdate($url){

		// Insert statement
		$stmt = $this->getDbConnection()->prepare("INSERT INTO backlinks(url) VALUES (:url) ON DUPLICATE KEY UPDATE creation_date = NOW()");

		// URL
		$data = array(
			'url' => $url
		);
	
		// Find all the backlinks
		$stmt->execute($data);
	}


	/**
	 * Analyze all the backlink
	 */
	function analyze()
	{
		// Find all the backlinks where statusCode is Null
		$stmt = $this->getDbConnection()->prepare("SELECT * FROM backlinks");

		// Find all the backlinks
		$stmt->execute();

		// For each links found we analyze the content of the page.
		while ($backlink = $stmt->fetch(PDO::FETCH_OBJ)) {
			//$this->analyzeBacklink($backlink);
			$this->analyzeRanking($backlink);
		}
	}

	/**
	 * Analyze all the backlink
	 */
	function generateDisavow()
	{
		// Find all the backlinks where statusCode is Null
		$stmt = $this->getDbConnection()->prepare("SELECT DISTINCT domain FROM backlinks WHERE disavow=1");

		// Find all the backlinks
		$stmt->execute();

		// Plain text 
		header("Content-Type: text/plain");
		header('Content-Disposition: attachment; filename="disavow_'.$this->conf->website.'.txt"');

		// For each links found we analyze the content of the page.
		while ($backlink = $stmt->fetch(PDO::FETCH_OBJ)) {
			echo "domain:$backlink->domain\n";
		}
	}

	/**
	 * Analyze all the backlink
	 */
	function report($status, $found=null, $disavow=null)
	{
		$sql = "SELECT id,url,http_code,label,target,alexa_global_rank,disavow,count(domain) as cnt FROM backlinks WHERE ";
		$and = false;

		// Filter on a status
		if ($status != null) {
			$sql .= " http_code = $status";
			$and = true;
		}

		// Filter if the backlink has been found or not
		if ($found != null) {
			if ($and) $sql .= " AND ";

			if ($found == false) {
				$sql .= "target IS NULL";
			} else {
				$sql .= "target IS NOT NULL";
			}
			$and = true;
		}

		// Filter on the disavow flag
		if ($disavow != null) {
			if ($and) $sql .= " AND ";
			if ($disavow == false) {
				$sql .= "disavow=0";
			} else {
				$sql .= "disavow=1";
			}
			$and = true;
		}
	
		$sql .= " GROUP BY domain ORDER BY cnt DESC";

		// Find all the backlinks where statusCode is Null
		$stmt = $this->getDbConnection()->prepare($sql);

		// Find all the backlinks
		$stmt->execute();

		$backlinks = $stmt->fetchAll(PDO::FETCH_OBJ);
		return $backlinks;
	}

	/**
	 * Extract rank page information
	 */
	function analyzeRanking($backlink)
	{

		// We don't analyse existing websites.
		if ($backlink->alexa_global_rank != 0) return;

		// Update statement
		$updt = $this->getDbConnection()->prepare("UPDATE backlinks SET alexa_global_rank=:alexa_global_rank where domain=:domain");

		$globalRanking = $this->getAlexaRank($backlink->domain);

		if ($globalRanking != 0){
			// Fetch the data
			$data = array(
				'domain' => $backlink->domain,
				'alexa_global_rank' => $globalRanking
			);

			if (! $updt->execute($data)) {
				print_r($updt->errorInfo());
			};
		}
		
	}

	/**
	 * Return the AlexaRank for a Website.
	 */
	private function getAlexaRank($domain)
    {
		$response = file_get_contents("https://www.alexa.com/minisiteinfo/" . urlencode($domain));
		echo $response;
		
		$dom = new \DomDocument();
		$dom->loadHTML($response);
		$nodes = (new \DomXPath($dom))->query("//div[contains(@class, 'data')]");
		if (isset($nodes[0]->nodeValue)) {
			$globalRanking = (int) str_replace(array(',', '.'), '', $nodes[0]->nodeValue);
			echo "Get GlobalRanking for $domain : $globalRanking\n";
			return $globalRanking;
		}
			
        return 0;
	}
	
	/**
	 * Extract HTML page information
	 */
	function analyzeBacklink($backlink)
	{
		// Update statement
		$updt = $this->getDbConnection()->prepare("UPDATE backlinks SET http_code=:http_code, target=:target, label=:label where id=:id");

		$url = $backlink->url;

		// We retreive the HTML page
		$result = $this->fetchPage($url);

		// Setup the data array
		$data = array(
			'id' => $backlink->id,
			'http_code' => $result['http_code'],
			'label' => null,
			'target' => null
		);

		// If the website return a positive response
		if ($data['http_code'] == 200) {
			$link = $this->extractLink($result['body'], $this->conf->website);
			if (isset($link['target']) && isset($link['label'])) {
				$data['target'] = $link['target'];
				$data['label'] = $link['label'];
			}
		}
		$updt->execute($data);
	}

	/** 
	 * Extract a link from a webpage
	 */
	function extractLink($body, $website)
	{

		// Initialize the DOM Document for HTML parsing
		libxml_use_internal_errors(true);
		$doc = new DOMDocument();

		// We parse the HTML page
		$doc->loadHTML($body);
		$xpath = new DOMXPath($doc);
		$nodeList = $xpath->query('//a/@href');

		// Initialize the result
		$link = array();

		// We try to find a link to our website
		for ($i = 0; $i < $nodeList->length; $i++) {
			if (strpos($nodeList->item($i)->value, $website)) {
				$link['target'] = $nodeList->item($i)->value;
				$link['label'] = $nodeList->item($i)->textContent;
				break;
			};
		};

		return $link;
	}

	/**
	 * Fetch page content like a Google spider.
	 */
	function fetchPage($url)
	{
		$header = array();
		$header[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$header[] = 'Cache-Control: max-age=0';
		$header[] = 'Content-Type: text/html; charset=utf-8';
		#$header[] = 'Transfer-Encoding: chunked'; 
		$header[] = 'Connection: keep-alive';
		$header[] = 'Keep-Alive: 300';
		$header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		$header[] = 'Accept-Language: en-us,en;q=0.5';
		$header[] = 'Pragma:';

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Call the URL
		$response = curl_exec($curl);

		// If the curl request failed
		if ($response === false) {
			$result['http_code'] = -1;
			$result['body'] = curl_error($curl);
		} else {
			// Extract the data
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$result['header'] = substr($response, 0, $header_size);
			$result['body'] = substr($response, $header_size);
			$result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$result['last_url'] = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		}

		curl_close($curl);
		return $result;
	}
}
