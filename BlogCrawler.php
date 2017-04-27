<?php
    

	include("simple_html_dom.php");
	error_reporting(E_ALL);
	set_time_limit(10000);
	
		class Crawler
	{ 
	
	public $wordsToFind;	
	public $outFile;
	public $dataFile;
	public $URLFilterRules = array();
	public $URLFollowRules = array();
	public $URLCaptures = array();
	public $divTypeRules = array();
	public $visited = array();
	public $articleCount=0;
	public $maximumArticleCount=200;
	public $additionalInfo;
	public $pagecount=0;
	
	function addFilter($rule){						//filters specify url's to not follow
		array_push($this->URLFilterRules, $rule);	//will not follow if matches exist. Optional
	}
	function addDivTypeRule($rule){						//Optional
		array_push($this->divTypeRules, $rule);	//	limit the crawler to scouring only certain div types.
												//great for avoiding ads/ saving time
	}
	function addFollow($rule){						//follow rules specify which urls to follow	
		array_push($this->URLFollowRules, $rule);	//will follow iff at least one match exists
	}
	function addCapture($rule){						//specifies which docs to capture. necessary
		array_push($this->URLCaptures, $rule);
	}
	function shouldFollow($url){					//applies following rules.	necessary
		$success = false;
		foreach($this->URLFollowRules  as $rule){
			if(preg_match($rule, $url))
				$success = true;
		}
		if(!$success)
			return false;
		
		foreach($this->URLFilterRules  as $rule){
			if(preg_match($rule, $url))
				$success = false;
		}
		
		
		return $success;
	}
	
	function shouldCapture($url){				//applies capture rules.
		foreach($this->URLCaptures as $rule){
			if(preg_match($rule, $url))
				return true;
		}
	}
	
	
	function crawl($base,$relurl, $depth)	//base remains the same, relurl determines which page is displayed
	{	
		
		$url = $base.$relurl;
		echo "getting ".$url."<br>";
		$c = curl_init($url);
		$agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		
		
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);	//fetch page with curl
		curl_setopt($c, CURLOPT_FAILONERROR, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_USERAGENT, $agent);
		
		
		
		
		$page = curl_exec($c);		
		//print_r(curl_getinfo($c));
		$html =  str_get_html($page);
		$pagecount=0;
		
		
		if(!is_object($html)){							//check for request failure
			echo "Failed to get page: ".$url."<br>";
			print_r(curl_getinfo($c));
			return;
		}
		else
			echo "Got Page: ".$url."<br>";
		
		if($this->shouldCapture($url)){					//check capture rules
			$this->articleCount++;
			$this->handleDocumentInfo($html, $url);
			return;
		}
		if($depth == 0)
			return;
		
		
		
		if(!empty($this->divTypeRules))   {   				//apply div type rules
			foreach($this->divTypeRules as $rule)
				foreach($html->find($rule)as $element){
				//	echo "found element";
					foreach($element->find('a') as $link)
						$this->handleLink($base, $link->getAttribute('href'), $depth);
				}
		}
		else{
			foreach($html->find('a') as $link){			//get all links
				 $this->handleLink($base, $link->getAttribute('href'), $depth);
			}
		}
	}
	
	function handleLink($base, $link, $depth){
		echo "Found Link: ".$link;
		if($this->shouldFollow($base.$link)&&$this->articleCount<$this->maximumArticleCount&& $this->pagecount< $depth[0]&&!in_array($base.$link, $this->visited)){	//check if not already visited, under page budget, and obeys follow rules
			array_push($this->visited, $base.$link);
			if(strpos($link, "http")!==FALSE)
				$this->crawl($link, "", array_splice($depth,0,1));
			else
				$this->crawl($base, $link, array_splice($depth,0,1));
		}
		//else
		//	echo "not following: ".$base.$link."<br>";
	}
	
	
  function getData($from)
  {
	$data = array();
	$bio = strtolower($bio);
	$count = 0;
	foreach($this->wordsToFind as $word){
	if(!empty($word))
		$occ = substr_count($bio,$word);
		$count +=$occ;
		$data[$word] = $occ;
	}
	$data['total']=$count;
	return $data;
  }


  function handleDocumentInfo($html, $url) 
  { 
  
	$author = preg_split("'\/|\.'", $url)[2];
	$outFile = fopen("output/".$author.".html","w");
	//$data = $this->getData($html);	if you're only collecting data
	
	
	fwrite($outFile, $html);
	
	
	
	echo "handled ".$url."<br>";
	unset($html);
  } 
}
	function trim_value(&$value) 
	{ 
		$value = trim($value); 
	}

  echo "working properly ";
 
  //$wordsFile = fopen("keywords.txt", "r");		
  //$contents = fread($wordsFile, 10000);
  //$words = preg_split('/\s+\n/', $contents);
  //foreach($words as $word)
	//trim_value($word);
  //echo "searching for ".implode(",",$words)."<br>";
  
  
  
  //$dataFile = fopen("data/data.csv","w");
 
 // fputcsv( $dataFile , $columnHeaders);
  //for goes here
  
	 
	 
	  $c = new Crawler();
	 
	  //$c->outFile = $outFile;
	 // $c->dataFile = $dataFile;
	  $c->addFilter("#\.(jpg|jpeg|gif|png)$# i");	//just by default
	  
	  
	   $c->addFollow("#[a-z][a-z]\/#");
	  $c->addFollow("#.*\.blogspot\.com.*#");		//follow these
	  
	  $c->addCapture("#.*\.blogspot\.com.*#");		//then capture them
	  
	  
	 
	  $c->addDivTypeRule('div[class=maintable]');	
	  
	  
	  $c->maximumArticleCount = 10000000;			//no limit
	 
	  $levelCounts = array(100, 100);	//100 on first tier, 100 pages on second
	  
	  
	  $c->crawl("http://indigenoustweets.com/blogs/", "", $levelCounts);	//starting here
	  
  
  
  //fclose($outFile);
  //fclose($c->dataFile);
  
  //and ends here
  
  echo "Terminated";
  
   
   
   