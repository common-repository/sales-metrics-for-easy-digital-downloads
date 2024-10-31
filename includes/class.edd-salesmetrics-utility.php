<?php
class EDD_Sales_Metrics_Util{
	public function __constructor(){
	}

	// convert 2008-09-07 12:33:44 to 09/07/08
	static function normalizeDateOnly($date){
		$dateArr1 = split(' ', $date);
		$dateArr2 = split('-', $dateArr1[0]);
		return $dateArr2[1] .'/'. $dateArr2[2] .'/'. substr($dateArr2[0], 2);
	}

	static function getDomainByUrl($url){
		$urlParts = parse_url($url);
		$domain = preg_match('#(?:^|\.)([a-z0-9]+\.(?:[a-z]{2,}|[a-z.]{5,6}))$#i', $urlParts['host'], $tmp) ? $tmp[1] : $urlParts['host'];

		return $domain;
	}

	static function email_is_valid($email) {
		if (substr_count($email, '@') != 1)
			return false;
		if ($email{0} == '@')
			return false;
		if (substr_count($email, '.') < 1)
			return false;
		if (strpos($email, '..') !== false)
			return false;
		$length = strlen($email);
		for ($i = 0; $i < $length; $i++) {
			$c = $email{$i};
			if ($c >= 'A' && $c <= 'Z')
				continue;
			if ($c >= 'a' && $c <= 'z')
				continue;
			if ($c >= '0' && $c <= '9')
				continue;
			if ($c == '@' || $c == '.' || $c == '_' || $c == '-')
				continue;
			return false;
		}
		$TLD = array (
			'COM',	'NET',
			'ORG',	'MIL',
			'EDU',	'GOV',
			'BIZ',	'NAME',
			'MOBI',  'INFO',
			'AERO',  'JOBS',
			'MUSEUM'
		);
		$tld = strtoupper(substr($email, strrpos($email, '.') + 1));
		if (strlen($tld) != 2 && !in_array($tld, $TLD))
			return false;
		return true;
	}

	static function isInteger($input){
		return preg_match('@^[-]?[0-9]+$@',$input) === 1;
	} 

	static function get_user_browser() 
	{ 
		$u_agent = $_SERVER['HTTP_USER_AGENT']; 
		$ub = ''; 
		if(preg_match('/MSIE/i',$u_agent)) 
		{ 
			$ub = "ie"; 
		} 
		elseif(preg_match('/Firefox/i',$u_agent)) 
		{ 
			$ub = "firefox"; 
		} 
		elseif(preg_match('/Safari/i',$u_agent)) 
		{ 
			$ub = "safari"; 
		} 
		elseif(preg_match('/Chrome/i',$u_agent)) 
		{ 
			$ub = "chrome"; 
		} 
		elseif(preg_match('/Flock/i',$u_agent)) 
		{ 
			$ub = "flock"; 
		} 
		elseif(preg_match('/Opera/i',$u_agent)) 
		{ 
			$ub = "opera"; 
		} 
		
		return $ub; 
	} 

	// compare against current time and return ..sec|min|day|..ago
	static function distanceOfTime($date)
	{
		if(empty($date)) {
			return "No date provided";
		}
		
		$periods	= array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths	= array("60","60","24","7","4.35","12","10");
		
		$now		= time();
		$unix_date	= strtotime($date);
		
		   // check validity of date
		if(empty($unix_date)) {	
			return "Bad date";
		}

		// is it future date or past date
		if($now > $unix_date) {	
			$difference	= $now - $unix_date;
			$tense		= "ago";
			
		} else {
			$difference	= $unix_date - $now;
			$tense		= "from now";
		}
		
		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}
		
		$difference = round($difference);
		
		if($difference != 1) {
			$periods[$j].= "s";
		}
		
		return "$difference $periods[$j] {$tense}";
	}
}
?>
