<?php
/**
* This class helps to monitor someone's timeline in real time and returns KEYs
* originaly made for @planetside2 beta keys public giveaway
* 
* Using this piece of code I got a few keys for friends and myself.
* I don't need it anymore so I am releasing it. Do what you want of it.
* Actually it was a great chance for me to learn Twitter's inner working.
*
*** If you are not familiar with PHP (www.php.net) and does not know how to run a PHP script from command line (CLI),
*** please don't bother reading further into this script.
*
* Will match most key formated like this: (and put it in order if able to do so)
	XXXX-XXXX-XXXX-XXXX-XXXX
	XXXXXXXXXXXXXXXXXXXX
	xxxx-xxxx-xxxx-xxxx-xxxx
	xxxxxxxxxxxxxxxxxxxx
	(1)XXXX- (3)XXXX- (4)XXXX- (5)XXXX- (2)XXXX-
	[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX- [2]XXXX-
	[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX [2]XXXX-
	[1]XXXX[3]XXXX[4]XXXX[5]XXXX[2]XXXX
	(3)Z6TM-(1)ZTKX-[4]FHHE-FCK2 (2)DKM2-
	(5)KKT6-RHXE(z)-2E-[ZZ)N6-A943(ZZ)-J6NR
	(2)HH66-KM4K-4X2H (1)CXJ3-GGGE-
	-> and some more (improvable if you have the regexpr mojo)
*
* The script can (often) detect keys over multiple tweets (even if not perfect)
* The twitter "API limit rate" does not apply in this case since it is tracking a real-time stream of tweets
* Since it is a live stream, the key will appear in real-time just a second after published by @planetside2, no display cache latency.
* You will be the first to get the key if you are ready to copy/paste it from the command shell to SOE's code registration page.
* 
****** Requirements:
* I am using a forked version of the great lib 'Phirehose' called Lethak_Twitter_Phirehose but you can simply use the original available here:
* https://github.com/fennb/phirehose/tree/master/lib
* basically you will have to find/replace 'Lethak_Twitter_Phirehose' with 'Phirehose' in this script before running it (not a great deal)
* This lib is mandatory and of great help taking care of the 'live-streaming' layer.
*
****** Exemple of use:

	# create a file called 'soe.php' with:

	// (dont forget to include Phirehose and Soe_Pskeystream before this line)
	$Stream = new Soe_Pskeystream('USSERNAME', 'PASSWORD', LethaK_Twitter_Phirehose::METHOD_FILTER);
	// dont forget to replace USSERNAME and PASSWORD with your account's login and password,
	// used to connect to Twitter's public stream API.
	$Stream->setFollow(array("247430686"));//@planetside2
	$Stream->consume();

	# Then you can run it from CLI like this:

	$ php soe.php
	
	# if you want to call if from a browser you will want to check the 'max execution time' setting from PHP. (must be unlimited-like)
	# Then, get ready to copy/paste fast ;)

****** Customisations:
* If you want to improve and or enable/disable key's extraction method, take a look at the function 'extractKeys'
* You can create your own extraction method and plug it in the flow within 'extractKeys'
*
****** Bonus:
* You will note the trollStatus function, (disabled by default), don't abuse with that if you want to enable it ;)
* I am not providing you with the code able to run It but it is not a great deal. (Twitter application and Oauth stuff)
*
****** Author:
* A dirty script -not so quickly- made, and improved over time,
* by @remisouverain
*
**/
class Soe_Pskeystream extends Lethak_Twitter_Phirehose
{

	protected $pendingKeyPartList2 = array();
	protected $pendingKeyPartList2NeedKrsort = false;
	protected $pendingKeyPartList3 = array();

	protected function trollStatus($data, $correctKeys=array(), $inTime=null)
	{

		$_ReplyPool = array(
			"I think the answer is: ",
			"The answer is probably: ",
			"If I am wrong, blame the Vanu: ",
			"I think I got it solved: ",
			"Probably solved: ",
			"Just for the challenge: ",
			"If I am wrong, blame the VS: ",
			"My granny solved this key: ",
			"Key solved for fun: ",
		);

		$ReplyPool_ = array(
			" in approx %S%s",
			" in %S%s",
			" in just %S% seconds.",
			" in %S%s with one hand only!",
			" within %S% seconds ",
		);

		
		if(count($correctKeys)>0)
		{

			$status = '@'.$data['user']['screen_name'].' '.$_ReplyPool[rand(0,count($_ReplyPool)-1)].implode(', ', $correctKeys);
			if($inTime!==null)
			{
				$status.= str_replace('%S%', $inTime, $ReplyPool_[rand(0,count($ReplyPool_)-1)]);
			}

			$App = new Lethak_Twitter_Application(array(
				'consumerKey' => 'xxxxxxxxxx',
				'consumerSecret' => 'xxxxxxxxxx',
				'callbackUrl' => 'oob',
			));
			$App->setCli(true);
			$result = $App->status_update(array(
				'status' => $status,
				'in_reply_to_status_id' => $data['id'],
				'in_reply_to_user_id' => $data['user']['id'],
			));

			return $result;
		}
	}



	public function enqueueStatus($status)
	{
		/*
		* In this simple example, we will just display to STDOUT
		*/
		$data = json_decode($status, true);
		if ($data)
		{
			//echo(print_r($data,true).PHP_EOL);

			$isCnC = $this->checkForCommandAndControl($data);
			if(!$isCnC) $this->processDirectTweet($data);
		}
	}


	protected function checkForCommandAndControl($data)
	{
		if(
					array_key_exists('text', $data)
				&& 	array_key_exists('user', $data)
				&& 	$data['user']['screen_name']==="/*USERNAME*/"
				&& 	$data['in_reply_to_status_id']===null
				&& 	$data['in_reply_to_user_id']===null
				&& 	$data['text']!==null
				&& 	strlen($data['text'])>16
				&& 	substr($data['text'], 1,16)=="Soe_Pskeystream:"
		)
		{
			echo (PHP_EOL.PHP_EOL."===== Command & Control =====".PHP_EOL.PHP_EOL);

			$message = trim($data['text']);
			$messageParts = explode(':', $message);
			if(array_key_exists(1, $messageParts) && isset($messageParts[1]))
			{
				$KpV = explode('=',$messageParts[1]);

						//var_dump($KpV, explode('-', $KpV[1]));
				switch ($KpV[0])
				{
					case 'resetPendingKeyPartList':
					case 'resetPending':
						$this->pendingKeyPartList = null;
						$this->pendingKeyPartList = array();
						echo('pendingKeyPartList reseted'.PHP_EOL);
					break;
					
					case 'setPendingKeyPartList':
					case 'setPending':
						$this->pendingKeyPartList = null;
						$this->pendingKeyPartList = array();
						if(array_key_exists(1, $KpV)) $this->pendingKeyPartList = explode('-', $KpV[1]);
						echo('pendingKeyPartList set to: '.print_r($this->pendingKeyPartList,true).PHP_EOL);
					break;


					default:
					break;
				}
			}
			return true;
		}
		return false;
	}

	protected $pendingKeyPartList = array();
	protected function processDirectTweet($data)
	{
		if(
					array_key_exists('text', $data)
				&& 	array_key_exists('user', $data)
				&& 	in_array($data['user']['id'], $this->getFollow())
				&& 	$data['in_reply_to_status_id']===null
				&& 	$data['in_reply_to_user_id']===null
				&& 	$data['text']!==null
				//&& 	strlen($data['text'])>=20
		)
		{
			$timeStart = microtime(true);
			$data['text'] = str_replace(
				array('[BKB]','(BKB)','#BKB','(PT1)','(PT2)','[PT1]','[PT2]','^','*')
				, '',
				$data['text']
			);
			$keys = $this->extractKeys($data);
			$inTime = round((microtime(true) - $timeStart)*1000,2); //Seconds


			if(count($keys)>0)
			{
				
				echo(PHP_EOL."Tweet FROM @{$data['user']['screen_name']} : {$data['text']}".PHP_EOL);
				echo('====================='.PHP_EOL);
				echo('****  SOME KEYS  **** ('.time().')'.PHP_EOL);
				echo('====================='.PHP_EOL);

			}

			if(count($this->pendingKeyPartList)>0)
				echo('/!\ pendingKeyPartList: ( '.count($this->pendingKeyPartList).' / 5 ) /!\ '.implode('-',$this->pendingKeyPartList).PHP_EOL);

			if(count($this->pendingKeyPartList2)>0)
				echo('/!\ pendingKeyPartList2: ( '.strlen(implode('-',$this->pendingKeyPartList2)).' / 20 ) /!\ '.implode('-',$this->pendingKeyPartList2).PHP_EOL);

			if(count($this->pendingKeyPartList3)>0)
				echo('/!\ pendingKeyPartList3: ( '.strlen(implode('',$this->pendingKeyPartList3)).' / 20 ) /!\ '.implode('',$this->pendingKeyPartList3).PHP_EOL);

			if(count($keys)>0)
			{
				echo(PHP_EOL.print_r($keys,true).PHP_EOL);

				///*if($data['user']['screen_name']==="lethak")*/ $this->trollStatus($data, $keys, $inTime); //UNCOMMENT ?
			}

		}
		
	}


	protected function extractKeys($data)
	{
		$text = trim($data['text']);

		$keys = array();
		$keys = array_merge($keys, $this->discoverKeys_0($text));
		$keys = array_merge($keys, $this->discoverKeys_1($text));
		
		if(count($keys)<=0)
			$keys = array_merge($keys, $this->discoverKeys_2($text));
		/*
		if(count($keys)<=0)
			$keys = array_merge($keys, $this->discoverKeys_3($text));
		if(count($keys)<=0)
			$keys = array_merge($keys, $this->discoverKeys_4($text));
		*/

		if(
			count($keys)<=0
			&& count($this->pendingKeyPartList2)<=0
		)
			$keys = array_merge($keys, $this->discoverKeys_5($text));
		if(
			count($keys)<=0
			&& count($this->pendingKeyPartList2)<=0
			&& count($this->pendingKeyPartList3)<=0
		)
			$keys = array_merge($keys, $this->discoverKeys_overMultipleStatus_0($text));

		foreach ($keys as $k => $v)
		{
			$keys[$k] = strtoupper(str_replace('-', '', $v));
		}

		return $keys;
	}

	// Simple keys in the following formats:
	/*
		XXXX-XXXX-XXXX-XXXX-XXXX
		XXXXXXXXXXXXXXXXXXXX
		xxxx-xxxx-xxxx-xxxx-xxxx
		xxxxxxxxxxxxxxxxxxxx
	*/
	protected function discoverKeys_0($haystack, $needleRegexp='/[a-zA-Z\-0-9]{20,24}/')
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

	// Keys in the following formats:
	/*
		(1)XXXX- (3)XXXX- (4)XXXX- (5)XXXX- (2)XXXX-
		[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX- [2]XXXX-
		[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX [2]XXXX-
		[1]XXXX[3]XXXX[4]XXXX[5]XXXX[2]XXXX

		note that '-' is not always present
	*/
	protected function discoverKeys_1_old($haystack, $needleRegexp='@[\[(0-9)\]]{3,3}[a-zA-Z\-0-9]{4,4}@')
	{
		$results = array();
		$parts = array();
		$matches = array();
		$found = preg_match_all($needleRegexp, $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			foreach($matches[0] as $value)
			{
				$parts[] = $value;
			}

			$keyBuilderSequence = 0;
			$keyBuilder = array();
			foreach($parts as $fullBlockPart)
			{
				/* Parts will look like that
				Array
				(
				    [0] => (1)XXXX
				    [1] => (3)YYYY
				    [2] => (4)EEEE
				    [3] => (5)RRRR
				    [4] => (2)CCCC
				    [5] => [1]AAAA
				    [6] => [3]GGGG
				    [7] => [4]TTTT
				    [8] => [5]VVVV
				    [9] => [2]BBBB
				    [10] => [1]NNNN
				    [11] => [3]JJJJ
				    [12] => [4]KKKK
				    [13] => [5]llll
				    [14] => [2]QQQQ
				)
				*/

				$keyBuilderSequence++;
				$nearBlockPart = str_replace(
					array('(',')','[',']'),
					'',
					$fullBlockPart
				);
				$blockPartSeq = substr($nearBlockPart, 0, -4);
				$blockPart = substr($nearBlockPart, -4);
				//var_dump($blockPart, $blockPartUnit); exit;
				$keyBuilder[((int)$blockPartSeq)] = "".$blockPart;

				if($keyBuilderSequence>=5)
				{
					ksort($keyBuilder, SORT_NUMERIC);
					$results[] = implode('', $keyBuilder);
					$keyBuilder = array();
					$keyBuilderSequence = 0;
				}
			}
		}
		return $results;
	}

	// Keys in the following formats:
	/*
		(1)XXXX- (3)XXXX- (4)XXXX- (5)XXXX- (2)XXXX-
		[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX- [2]XXXX-
		[1]XXXX- [3]XXXX- [4]XXXX- [5]XXXX [2]XXXX-
		[1]XXXX[3]XXXX[4]XXXX[5]XXXX[2]XXXX
		(3)Z6TM-(1)ZTKX-[4]FHHE-FCK2 (2)DKM2-
		(5)KKT6-RH*XE(z)-2E-[ZZ)N6-A943(ZZ)-J6NR
		note that '-' is not always present
	*/
	protected function discoverKeys_1($haystack)
	{
		$results = array();

		$matches = array();
		$found = preg_match_all('@([1-5])[)\]]([A-Z0-9\-^\b]*)@', $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			/*
			Array
			(
			    [0] => Array
			        (
			            [0] => 3)Z6TM-
			            [1] => 1)ZTKX
			            [2] => 4)FHHE-FCK2
			            [3] => 2)DKM2-
			        )

			    [1] => Array
			        (
			            [0] => 3
			            [1] => 1
			            [2] => 4
			            [3] => 2
			        )

			    [2] => Array
			        (
			            [0] => Z6TM-
			            [1] => ZTKX
			            [2] => FHHE-FCK2
			            [3] => DKM2-
			        )
			)
			*/
			foreach($matches[1] as $i => $keyOrder)
			{
				$keyPart = str_replace('-', '', $matches[2][$i]);
				if($keyPart===null || $keyPart=='') continue;

				if(!array_key_exists((int)$keyOrder, $this->pendingKeyPartList2))
					$this->pendingKeyPartList2[(int)$keyOrder] = $keyPart;
				else
				{
					$this->pendingKeyPartList2[] = $keyPart;
					$this->pendingKeyPartList2NeedKrsort = true;
				}

				// Key may be over multiple tweets
				$keyStringSoFar = implode('', $this->pendingKeyPartList2);
				if(strlen($keyStringSoFar)>=20)
				{
					ksort($this->pendingKeyPartList2, SORT_NUMERIC);
					$keyStringSoFar = implode('', $this->pendingKeyPartList2);
					$results[] = $keyStringSoFar;

					if($this->pendingKeyPartList2NeedKrsort)
					{
						krsort($this->pendingKeyPartList2, SORT_NUMERIC);
						$keyStringSoFar = implode('', $this->pendingKeyPartList2);
						$results[] = $keyStringSoFar;
						$this->pendingKeyPartList2NeedKrsort = false;
					}

					$this->pendingKeyPartList2 = null;
					$this->pendingKeyPartList2 = array();
				}
			}
			$keyStringSoFar = implode('', $this->pendingKeyPartList2);
			if(strlen($keyStringSoFar)<20)
			{
				// pending key
				//check for "(5)KKT6-RHXE(z)-2E-(ZZ)N6-A943(ZZ)-J6NR" just in case
				$matches2 = array();
				//$found2 = preg_match_all('@[A-Z0-9]{2,4}@', $haystack, $matches2, PREG_OFFSET_CAPTURE);
				$found2 = preg_match_all('@[A-Z0-9\-^\b]*@', $haystack, $matches2, PREG_OFFSET_CAPTURE);
				if($found2>0)
				{
					/*$matches2 = Array
					(
					    [0] => Array
					        (
					            [0] => Array
					                (
					                    [0] => KKT6
					                    [1] => 3
					                )

					            [1] => Array
					                (
					                    [0] => RHXE
					                    [1] => 8
					                )

					            [2] => Array
					                (
					                    [0] => 2E
					                    [1] => 16
					                )

					            [3] => Array
					                (
					                    [0] => ZZ
					                    [1] => 20
					                )

					            [4] => Array
					                (
					                    [0] => N6
					                    [1] => 23
					                )

					            [5] => Array
					                (
					                    [0] => ZZ
					                    [1] => 26
					                )

					            [6] => Array
					                (
					                    [0] => 43
					                    [1] => 29
					                )

					            [7] => Array
					                (
					                    [0] => ZZ
					                    [1] => 32
					                )

					            [8] => Array
					                (
					                    [0] => J6NR
					                    [1] => 36
					                )

					        )

					)*/
					$keyStringSoFar2 = '';
					foreach ($matches2[0] as $i => $value)
					{
						$keyPart 			= trim($value[0]);
						if($keyPart===null || $keyPart==='') continue;
						$offsetInHaystack 	= (int)$value[1];
						//check for "(ZZ)" cases
						$previousCHR = substr($haystack, $offsetInHaystack-1,1);
						$nextCHR = substr($haystack, $offsetInHaystack+1,1);
						if(
							($previousCHR=='(' || $previousCHR=='[')
							||
							(!is_int($nextCHR) && !ctype_upper(''.$nextCHR) && strlen($keyPart)===1)
						
						){/*DNT*/}
						else $keyStringSoFar2.=$keyPart;
					}
					$keyStringSoFar2 = str_replace('-', '', $keyStringSoFar2);
					if(strlen($keyStringSoFar2)>=20)
					{
						$results[] = substr($keyStringSoFar2, 0,20);
						$this->pendingKeyPartList2 = null;
						$this->pendingKeyPartList2 = array();
					}
				}
				 
			}
		}
		return $results;
	}

	// ex "(2)HH66-KM4K-4X2H (1)CXJ3-GGGE-"
	protected function discoverKeys_2($haystack)
	{
		$results = array();
		$parts = array();
		$fragments = array();
		


		$matches_0 = array();
		$countSymbolMinus = preg_match_all('/[-]/', $haystack, $matches_0, PREG_PATTERN_ORDER);
		if($countSymbolMinus<3 || $countSymbolMinus>4) return $results;

		$needleRegexp_1='@[\[(0-9)\]]{3,3}[a-zA-Z\-0-9]{4,4}@';
		$matches_1 = array();
		$found = preg_match_all($needleRegexp_1, $haystack, $matches_1, PREG_PATTERN_ORDER);

		if($found>0)
		{

			foreach($matches_1[0] as $value)
			{
				$parts[] = $value;
			}

			$results_1 = array();
			foreach($parts as $fullBlockPart)
			{

				$nearBlockPart = str_replace(
					array('(',')','[',']'),
					'',
					$fullBlockPart
				);
				$blockPartSeq = substr($nearBlockPart, 0, -4);
				$blockPart = substr($nearBlockPart, -4);
				//var_dump("PARTS", $blockPart, $blockPartSeq);
				$results_1[((int)$blockPartSeq)] = "".$blockPart;

				if(count($results_1)>0)
				{
					ksort($results_1, SORT_NUMERIC);
				}
			}
		}
		if(count($results_1)>0 && count($results_1)<4)
		{
			// on est dans le cas "(2)HH66-KM4K-4X2H (1)CXJ3-GGGE-"


			$matches = array();
			$found = preg_match_all('@([A-Z0-9]{4}[\-\w]{0,1}){1,4}@', $haystack, $matches, PREG_PATTERN_ORDER);
			if($found>0)
			{

				foreach($matches[0] as $value)
				{

					$fragments[] = $value;
				}
			}
			//if(count($fragments)!==count($results_1)) return array();


			$keyBuilder = array();
			$keyString = '';
			foreach($fragments as $k=>$v)
			{
				$searchResult = array_search(substr($v, 0,4), $results_1);
				if($searchResult!==false) $keyBuilder[$searchResult] = $v;
			}
			if(count($keyBuilder))
			{
				ksort($keyBuilder, SORT_NUMERIC);
				$keyString = implode('',$keyBuilder);
			}
			$keyString = str_replace(array(' ','-'), '', $keyString);
			if(strlen($keyString)==20) $results[] = $keyString;

		}
		return $results;

		
	}

	// overMultiple or not
	// ex "1)1111-2222-33 " then "2]33-4444-5555"
	protected function discoverKeys_3($haystack)
	{
		$results = array();
		$matches = array();
		// looking for X in the (X) or [X] fragment
		//$found = preg_match_all('@[(\[]([1-5])[)\]]([A-Z0-9\-]*)@', $haystack, $matches, PREG_PATTERN_ORDER);
		$found = preg_match_all('@([1-5])[)\]][\ ]?([0-9A-Z\-^\b]*)@', $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			/*
			Array
			(
			    [0] => Array
			        (
			            [0] => (1)1111-2222-33
			            [1] => (2)334444ZZZZZ
			        )

			    [1] => Array
			        (
			            [0] => 1
			            [1] => 2
			        )

			    [2] => Array
			        (
			            [0] => 1111-2222-33
			            [1] => 334444ZZZZZ
			        )
			)
			*/
			foreach($matches[1] as $i => $keyOrder)
			{
				$keyPart = str_replace('-', '', $matches[2][$i]);

				if(!array_key_exists((int)$keyOrder, $this->pendingKeyPartList2))
					$this->pendingKeyPartList2[(int)$keyOrder] = $keyPart;
				else
				{
					$this->pendingKeyPartList2[] = $keyPart;
					$this->pendingKeyPartList2NeedKrsort = true;
				}

				// Key may be over multiple tweets
				$keyStringSoFar = implode('', $this->pendingKeyPartList2);
				if(strlen($keyStringSoFar)>=20)
				{
					ksort($this->pendingKeyPartList2, SORT_NUMERIC);
					$keyStringSoFar = implode('', $this->pendingKeyPartList2);
					$results[] = $keyStringSoFar;

					if($this->pendingKeyPartList2NeedKrsort)
					{
						krsort($this->pendingKeyPartList2, SORT_NUMERIC);
						$keyStringSoFar = implode('', $this->pendingKeyPartList2);
						$results[] = $keyStringSoFar;
						$this->pendingKeyPartList2NeedKrsort = false;
					}

					$this->pendingKeyPartList2 = null;
					$this->pendingKeyPartList2 = array();
				}
			}

		}
		return $results;
	}

	// overMultiple or not
	// ex "1111-2222-33[1st"
	// ex "33-4444-55(2nd"
	protected function discoverKeys_4($haystack)
	{
		$results = array();
		$matches = array();
		// looking for X in the (X) or [X] fragment
		$found = preg_match_all('@([0-9A-Z\-^\b]*)[\ ]?[(\[]([1-5])@', $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			/*
			Array
			(
			    [0] => Array
			        (
			            [0] => 1)1111-2222-33
			            [1] => 2)334444ZZZZZ
			        )

			    [1] => Array
			        (
			            [0] => 1
			            [1] => 2
			        )

			    [2] => Array
			        (
			            [0] => 1111-2222-33
			            [1] => 334444ZZZZZ
			        )
			)
			*/
			foreach($matches[2] as $i => $keyOrder)
			{
				$keyPart = str_replace('-', '', $matches[1][$i]);
				if(!array_key_exists((int)$keyOrder, $this->pendingKeyPartList2))
					$this->pendingKeyPartList2[(int)$keyOrder] = $keyPart;
				else
				{
					$this->pendingKeyPartList2[] = $keyPart;
					$this->pendingKeyPartList2NeedKrsort = true;
				}
				// Key may be over multiple tweets
				$keyStringSoFar = implode('', $this->pendingKeyPartList2);
				if(strlen($keyStringSoFar)>=20)
				{
					ksort($this->pendingKeyPartList2, SORT_NUMERIC);
					$keyStringSoFar = implode('', $this->pendingKeyPartList2);
					$results[] = $keyStringSoFar;

					if($this->pendingKeyPartList2NeedKrsort)
					{
						krsort($this->pendingKeyPartList2, SORT_NUMERIC);
						$keyStringSoFar = implode('', $this->pendingKeyPartList2);
						$results[] = $keyStringSoFar;
						$this->pendingKeyPartList2NeedKrsort = false;
					}


					$this->pendingKeyPartList2 = null;
					$this->pendingKeyPartList2 = array();
				}
			}

		}
		return $results;
	}

	// overMultiple or not
	// ex "1111-2222-33[1st"
	// ex "33-4444-55(2nd"
	protected function discoverKeys_5($haystack)
	{
		$results = array();
		$matches = array();
		$found = preg_match_all('@[$0-9A-Z\-^\b]{3,24}@', $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			/*
			Array
			(
			    [0] => Array
			        (
			            [0] => GDF2-PAZP
			            //[1] => 4444-5555
			        )

			)
			*/
			foreach($matches[0] as $keyPart)
			{
				// Key may be over multiple tweets
				$this->pendingKeyPartList3[] = str_replace('-', '', $keyPart);;
				$keyStringSoFar = str_replace('-', '', implode('', $this->pendingKeyPartList3));
				if(strlen($keyStringSoFar)>=20)
				{
					// In order and reversed, will output 2 keys, only one will be the good one
					ksort($this->pendingKeyPartList3, SORT_NUMERIC);
					$keyStringSoFar = str_replace('-', '', implode('', $this->pendingKeyPartList3));
					$results[] = $keyStringSoFar;

					krsort($this->pendingKeyPartList3, SORT_NUMERIC);
					$keyStringSoFar = str_replace('-', '', implode('', $this->pendingKeyPartList3));
					$results[] = $keyStringSoFar;

					$this->pendingKeyPartList3 = null;
					$this->pendingKeyPartList3 = array();
				}
			}

		}
		return $results;
	}

	protected function discoverKeys_overMultipleStatus_0($haystack)
	{
		$results = array();
		$matches = array();
		$found = preg_match_all('@[A-Z0-9]{4}@', $haystack, $matches, PREG_PATTERN_ORDER);
		if($found>0)
		{
			foreach($matches[0] as $value)
			{
				$this->pendingKeyPartList[] = $value;
			}
		}
		if(count($this->pendingKeyPartList)>=5)
		{
			$results[0] = '';	
			foreach ($this->pendingKeyPartList as $key => $value)
			{
				$results[0].= $value;
			}
			$this->pendingKeyPartList = null;
			$this->pendingKeyPartList = array();
		}
		return $results;
	}
}