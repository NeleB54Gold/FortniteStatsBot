<?php

class FortniteTracker {
	# Unofficial API Endpoint [https://fortnitetracker.com/]
	public $endpoint = 'https://api.fortnitetracker.com/v1';
	# API Token
	private $token = '';
	# Cache time
	public $cache_time = 60 * 60 * 2;
	# Request timeout
	public $r_timeout = 5;
	# Database class
	private $db = [];
	
	# Set configs
	public function __construct ($db = []) {
		if (is_a($db, 'Database') && $db->configs['redis']['status']) $this->db = $db;
	}
	
	# Get player stats from every platform
	public function getPlayer ($tag, $platform = 'pc') {
		$r = $this->request('/profile/pc/' . $tag);
		if ($r['error'] == 'Player Not Found') {
			$r = $this->request('/profile/psn/' . $tag);
			if ($r['error'] == 'Player Not Found') {
				$r = $this->request('/profile/xbl/' . $tag);
			}
		}
		return $r;
	}
	
	# Custom API requests
	public function request ($src = '') {
		if (!isset($this->curl))	$this->curl = curl_init();
		$url = $this->endpoint . '/' . $src;
		if (is_a($db, 'Database') && $this->db->configs['redis']['status']) {
			$cache = $this->db->rget($url);
			if ($r = json_decode($cache, 1)) return $r;
		}
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> $url,
			CURLOPT_TIMEOUT			=> $this->r_timeout,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_HEADER			=> 0,
			CURLOPT_SSL_VERIFYPEER	=> 0,
			CURLOPT_HTTPHEADER		=> [
				'Accept: application/json',
				'TRN-Api-Key: ' . $this->token
			]
		]);
		$output = curl_exec($this->curl);
		if ($json_output = json_decode($output, 1)) {
			if (is_a($db, 'Database') && $this->db->configs['redis']['status']) $this->db->rset($url, json_encode($json_output), $this->cache_time);
			return $json_output;
		}
		if ($output) return $output;
		if ($error = curl_error($this->curl)) return ['ok' => 0, 'error_code' => 500, 'description' => 'CURL Error: ' . $error];
	}
}

?>