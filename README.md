planetside2.twitter-beta-key-catcher
====================================

Short description: Real-time Beta-key extractor | twitter @planetside2 via streaming API



 This class helps to monitor someone's timeline in real time and returns KEYs
 originaly made for @planetside2 beta keys public giveaway
 
 Using this piece of code I got a few keys for friends and myself.
 I don't need it anymore so I am releasing it. Do what you want of it.
 Actually it was a great chance for me to learn Twitter's inner working.

 If you are not familiar with PHP (www.php.net) and does not know how to run a PHP script from command line (CLI),
 please don't bother reading further into this script.

 Will match most key formated like this: (and put it in order if able to do so)
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

 The script can (often) detect keys over multiple tweets (even if not perfect)
 The twitter "API limit rate" does not apply in this case since it is tracking a real-time stream of tweets
 Since it is a live stream, the key will appear in real-time just a second after published by @planetside2, no display cache latency.
 You will be the first to get the key if you are ready to copy/paste it from the command shell to SOE's code registration page.
 
 Requirements:
==============
 I am using a forked version of the great lib 'Phirehose' called Lethak_Twitter_Phirehose but you can simply use the original available here:
 https://github.com/fennb/phirehose/tree/master/lib
 basically you will have to find/replace 'Lethak_Twitter_Phirehose' with 'Phirehose' in this script before running it (not a great deal)
 This lib is mandatory and of great help taking care of the 'live-streaming' layer.

 Exemple of use:
================
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

 Customisations:
================
 If you want to improve and or enable/disable key's extraction method, take a look at the function 'extractKeys'
 You can create your own extraction method and plug it in the flow within 'extractKeys'

 Bonus:
=======
 You will note the trollStatus function, (disabled by default), don't abuse with that if you want to enable it ;)
 I am not providing you with the code able to run It but it is not a great deal. (Twitter application and Oauth stuff)

 Author:
========
 A dirty script -not so quickly- made, and improved over time,
 by @remisouverain