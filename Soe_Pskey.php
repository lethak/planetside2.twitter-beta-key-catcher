<?php 

/**
* This class helps to query someone's timeline and returns KEYs
*
* originaly made for @planetside2 beta keys public giveaway
* 
* Deprecated !!!!
* See:  http://pastebin.com/kMhRx7yz
* 
* Got 2 keys for me and a friend using this.
*
* Will match any keys formated like this:
* XXXX-XXXX-XXXX-XXXX-XXXX
* XXXXXXXXXXXXXXXXXXXX
* xxxx-xxxx-xxxx-xxxx-xxxx
* xxxxxxxxxxxxxxxxxxxx
*
* Please read:
* Originally it required some Zend_Framework's components
* but I stripped this part ( the auto-register-key-to-station-account-part ;))
* Beware of twitter "API limit rate" (max 150 query per hour per IP in this case)
* 
* Exemple of use:
$keys = Soe_Pskey::fetchKeys(array(
  'screen_name' => 'planetside2',
  'count' => 10,
  'include_rts' => false,
  'include_replies' => false,
  'excludeKeysOlderThan' => $number,
));
var_dump($keys);exit;
* Quickly made by @remisouverain
**/
class Soe_Pskey
{
	
	protected static $UserAgent;
	protected static $SSL;
	protected static $CookieJar;

	public static function fetchKeys($params)
	{
		/*
			ApiGetStatusesUrl=https://api.twitter.com/1/statuses/user_timeline.json
			FrontProfileUrl=https://twitter.com
			FrontStatusesUrl=https://twitter.com/%screenname%/status
			FrontHashesUrl=https://twitter.com/search
		*/

		$result = array();

		$FrontProfileUrl = 'https://twitter.com';
		$ApiGetStatusesUrl = 'https://api.twitter.com/1/statuses/user_timeline.json';

		$querystringParams = array();
		if(array_key_exists('user_id', $params) && isset($params['user_id']) && $params['user_id']!=0)
		{
			$querystringParams['user_id'] = (int)(trim(''.$params['user_id']));
		}
		elseif (array_key_exists('screen_name', $params) && isset($params['screen_name']))
		{
			$querystringParams['screen_name'] = trim(''.$params['screen_name']);
		}
		else
		{
			throw new Exception('Invalid user_id or screen_name');
		}

		if(!array_key_exists('count', $params) || !isset($params['count']))
		{
			$params['count'] = 20;
		}

		if(!array_key_exists('include_rts', $params) || !isset($params['include_rts']))
		{
			$params['include_rts'] = true;
		}

		if(!array_key_exists('include_replies', $params) || !isset($params['include_replies']))
		{
			$params['include_replies'] = false;
		}

		if(!array_key_exists('keywords', $params) || !isset($params['keywords']) || !is_array($params['keywords']))
		{
			$params['keywords'] = array();
		}

		$factor = 1;
		$querystringParams['count'] = $factor*((int)(trim(''.$params['count']))); 
		if($querystringParams['count']>1000) $querystringParams['count'] = 1000;
		if($querystringParams['count']<0) $querystringParams['count'] = 1;
		$querystringParams['include_rts'] = ((int)(trim(''.$params['include_rts'])));
		$querystringParams['exclude_replies'] = (int)(!((bool)(int)(trim(''.$params['include_replies']))));

		$querystring = http_build_query($querystringParams);
		$url = $ApiGetStatusesUrl.'?'.$querystring;
		$response = @file_get_contents($url);
		$responseJson = @json_decode($response);

		if($responseJson===null || $responseJson==='')
		{
			// Try again
			$response = @file_get_contents($url);
			$responseJson = @json_decode($response);
		}

		// No more try
		if($responseJson===null || $responseJson==='')
		throw new Exception('service access unauthorized or temporarily overloaded');
		
		//$tweets = array();
		foreach($responseJson as $item)
		{

			$twOwnerVar = $item->user;
			$isRetweet = false;
			if(isset($item->retweeted_status))
			{
				$isRetweet = true;
				$twOwnerVar = $item->retweeted_status->user;
			}

			$twOwner = array();
			$twOwner['id'] = $twOwnerVar->id;
			$twOwner['screenname'] = $twOwnerVar->screen_name;
			if($twOwnerVar->name!==null) $twOwner['name'] = $twOwnerVar->name;

			// Save tweet now
			$tweet = array(
				'id' => $item->id,
				'date' => strtotime($item->created_at),
				'isRetweet' => (int)$isRetweet,
				'text'=>$item->text,
				'truncated'=>(int)$item->truncated,
				'user'=>$twOwner,
			);

			$extracted = self::_extractKeys($item->text);

			if(
					!array_key_exists('excludeKeysOlderThan', $params)
				||	!isset($params['excludeKeysOlderThan'])
				||	$params['excludeKeysOlderThan']===0
			)
			{
				$params['excludeKeysOlderThan'] = '60';
			}

			if(
					count($extracted)>0
				&&	time()-$tweet['date']<=$params['excludeKeysOlderThan']
			)
			$result[$tweet['id']] = array(
				'tweet' => $tweet,
				'keys' => $extracted,
			);
			//$result = array_merge($result, $loopResult);
		}

		return $result;
	}

	protected static function _extractKeys($haystack, $needleRegexp='/[a-zA-Z\-0-9]{20,24}/')
	{
		$results = array();
		$matches = array();
		$found = preg_match_all($needleRegexp, $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			foreach($matches[0] as $value)
			{
				$results[] = $value;
			}
		}
		return $results;
	}

}