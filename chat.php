<?php
session_start();
$_SESSION["questions"] = isset($_SESSION['questions']) && is_array($_SESSION['questions']) ? $_SESSION['questions'] : array();
$userInput = isset($_POST['text']) ? $_POST['text'] : "";

$file = fopen("db.txt",'r');
$questions = loadDb();
/*if($file){
	while(($line = fgets($file))){
		$raw = explode(";",$line);
		$qs = explode(",",$raw[0]);
		$raw[1] = trim($raw[1]);
		$as = explode(",",$raw[1]);
		
		array_push($questions, array('q'=>$qs,'a'=>$as));
	}
}*/
fclose($file);

$res = false;
$spoken = false;
if(is_array($_SESSION['questions']) && count($_SESSION['questions']) >= 1){
	if($userInput == ""){
	echo "I asked you a question..";
	echo $_SESSION['questions'][0]['q'];
	} else {
		//Check if the answer exists already, if it does, add a value to it, otherwise, add it.
		$foundAnswer = false;
		foreach($questions[$_SESSION['questions'][0]['id']]['a'] as &$answer){
			if(strtolower($answer['a']) == strtolower($userInput)){
				$answer['c']++;
				$foundAnswer = true;
				break;
			}
		}
		if(!$foundAnswer)
			array_push($questions[$_SESSION['questions'][0]['id']]['a'],array('a'=>$userInput,'c'=>1));
		
		exportDb($questions);
		unset($_SESSION['questions'][0]);
		
		foreach($questions as $q => $val){

			if(strtolower($val['q']) == strtolower($userInput)){
				if(count($val['a'])){
					$res = true;
					$high = false;
					//High will be set to the answer, together with it's count.
					foreach($val['a'] as $answer){
						if(!is_array($high) || $answer['c'] > $high['c'])
							$high = $answer;
					}
					echo $high['a'];
					$spoken = true;
					break;
				}

			}
			if($spoken){
				break;
			}

		}
		
		if(!$spoken){
			echo "Oh okay..";
		}
		
	}

} else if($userInput != "") {
	$found = false;
	
	foreach($questions as $q => $val){

			if(strtolower($val['q']) == strtolower($userInput)){
				$found = true;
				if(count($val['a'])){
					$res = true;
					$high = false;
					//High will be set to the answer, together with it's count.
					foreach($val['a'] as $answer){
						if(!is_array($high) || $answer['c'] > $high['c'])
							$high = $answer;
					}
					echo $high['a'];
					$spoken = true;
					break;
				}

			}
			if($res){
				break;
			}

	}

	
	if(!$spoken){
		
		if(rand(0,1)){
			echo "I don't know what to say.. but..";
			foreach($questions as $q => $val){
				if(!count($val['a'])){
					echo $val['q'];
					$val['id'] = $q;
					$_SESSION['questions'][0] = $val;
					$spoken = true;
					break;
				}
			}
		} else {
			echo "I don't know what to say.. but..";
			$spoken = true;
			$id = rand(0,count($questions)-1);
			$val = $questions[$id];
			echo $val['q'];
			$val['id'] = $id;
			$_SESSION['questions'][0] = $val;
			//rand(count($questions));
		}

		if(!$spoken){
			echo "nevermind";
		}
	} else {
			//Sometimes, ask a random question: even if it spoke
			echo "<br>";
			$spoken = true;
			$id = rand(0,count($questions)-1);
			$val = $questions[$id];
			echo $val['q'];
			$val['id'] = $id;
			$_SESSION['questions'][0] = $val;
			//rand(count($questions));
	}

	if(!$found){
		$questions[] = array('q'=>$userInput,'a'=>array());
		exportDb($questions);
	}
}
function loadDb(){
	$toReturn = false;
	$file = fopen("db.txt","r");
	if($file){
		$toReturn = array();
		while($line = fgets($file)){
		
			$results = array();
			
			if(!preg_match("/(.+);(.+)/",$line,$results)){
				$questions = explode(";",$line);
				$questions = $questions[0];
				$answers = array();
				$as = array();
			} else {
				$questions = $results[1];
				$answers = $results[2];
				$as = explode("~",$answers);
				foreach($as as &$answer){
					//$answer = "raz";
					$realmatches;
					$matches = preg_match("/(.+)\(([0-9]+)\)/",$answer,$realmatches);
					$answer = array('a'=>$realmatches[1],'c'=>$realmatches[2]);
				}
			}

			array_push($toReturn, array('q'=>$questions,'a'=>$as));

			
		}
		fclose($file);
	}
	return $toReturn;

}
function exportDb($array){
	$file = fopen("db.txt","a");
	file_put_contents("db.txt","");
	foreach($array as $line){
		$answers = "";
		if(count($line['a'])){
			$lastval = end(array_values($line['a']));
			foreach($line['a'] as $answer){
				$answers .= $answer['a']."({$answer['c']})".($answer === $lastval ? "" : "~");
			}
		}

		fwrite($file,"{$line['q']};".$answers."\n");
	}
	
	fclose($file);
}

?>