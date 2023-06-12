<?php

	require "vendor/autoload.php";
	header("Content-type:application/json");
	
	if($_GET['callback'] == "stepOneFunction")
		stepOneFunction();
	else if($_GET['callback'] == "stepTwoFunction")
		stepTwoFunction();
	else if($_GET['callback'] == "stepThreeFunction")
		stepThreeFunction();
	function scrapingFunction()
	{
		$HTTPresponse = file_get_contents("https://store.steampowered.com/search/?term=doom");
		libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML($HTTPresponse);
		$queryableDoc = new DOMXPath($doc);
		$games = [];
		for($i = 1; $i <= 3; $i++)
		{ 
			$scrapedPrice = $queryableDoc->query('//*[@id="search_resultsRows"]/a['. $i .']/div[2]/div[4]/div[2]');
			$price = $scrapedPrice[0]->nodeValue;
			$price = str_replace(',', '.', $price);
			$price = trim($price);
			
			$scrapedTitle = $queryableDoc->query('//*[@id="search_resultsRows"]/a['. $i. ']/div[2]/div[1]/span');
			$title = $scrapedTitle[0]->nodeValue;
			$temp = ["id" => $i, "Title" =>$title, "Price" => $price];
			array_push($games, $temp);
		}
		$finalData = ["Games"=>$games];
		print json_encode($finalData);
	}
	
	function stepOneFunction()
	{
		scrapingFunction();
	}
	
	function stepTwoFunction()
	{	
		$client=new \GuzzleHttp\Client();
		$JSONsosit=file_get_contents("php://input");
		
		$data = json_decode($JSONsosit, true);
		
		for($i = 0; $i < 4; $i++)
		{
			$interogareSerializata=["json"=>["query"=>'mutation{createGame(title : "'.$data[$i]["title"].'" price : '.$data[$i]["price"].'){title price}}']];
			$antet=["headers"=>["Content-Type"=>"application/json"]];
			$request = $client->postAsync("http://localhost:3000",$interogareSerializata,$antet);
			$request->wait();
		}
		
		$client=new \GuzzleHttp\Client();
		$interogareSerializata=["json"=>["query"=>"{allGames{id title price}}"]];
		$antet=["headers"=>["Content-Type"=>"application/json"]];
		$request = $client->postAsync("http://localhost:3000",$interogareSerializata,$antet);
		$request->then("processResponse")->wait();
		
	}

	function processResponse($response)
	{
		print $response->getBody();
	}
	
	function stepThreeFunction()
	{
		$client=new \GuzzleHttp\Client();
		$JSONsosit=file_get_contents("php://input");
		$data = json_decode($JSONsosit, true);
		$interogareSerializata=["json"=>["query"=>'{allGames(filter : {title : "'.$data["title"].'"}){id}}']];
		$antet=["headers"=>["Content-Type"=>"application/json"]];
		$request = $client->postAsync("http://localhost:3000",$interogareSerializata,$antet);
		$idJSON = $request->then("processFilterResponse")->wait();
		
		$id = json_decode($idJSON, true);
		$client=new \GuzzleHttp\Client();
		$interogareSerializata=["json"=>["query"=>'mutation{removeGame(id : '.$id["data"]["allGames"][0]["id"].'){id}}']];
		$antet=["headers"=>["Content-Type"=>"application/json"]];
		$request = $client->postAsync("http://localhost:3000",$interogareSerializata,$antet);
		$request->wait();
		
		$client=new \GuzzleHttp\Client();
		$interogareSerializata=["json"=>["query"=>"{allGames{id title price}}"]];
		$antet=["headers"=>["Content-Type"=>"application/json"]];
		$request = $client->postAsync("http://localhost:3000",$interogareSerializata,$antet);
		$request->then("processResponse")->wait();
	}
	
	function processFilterResponse($response)
	{
		return $response->getBody();
	}
?>