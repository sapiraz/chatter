<?php
session_start();
$_SESSION["questions"] = isset($_SESSION['questions']) && is_array($_SESSION['questions']) ? $_SESSION['questions'] : array();
$userInput = isset($_POST['text']) ? $_POST['text'] : "";
$file = fopen("db.txt",'r');
$questions = loadDb();
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
		if(is_array($questions[$_SESSION['questions'][0]['id']]['a']) && is_array($questions[$_SESSION['questions'][0]['id']]['a'][0])){
		foreach($questions[$_SESSION['questions'][0]['id']]['a'] as &$answer){
			if(strtolower($answer['a']) == strtolower($userInput)){
				$answer['c']++;
				$foundAnswer = true;
				break;
			}
		}
		}
		$foundAnswer = false;
		if(!$foundAnswer)
			array_push($questions[$_SESSION['questions'][0]['id']]['a'],array('a'=>$userInput,'c'=>1));
		//print_r($questions);
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
				if(count($val['a']) && is_array($val['a'][0])){
					$res = true;
					$high = false;
					//High will be set to the answer, together with it's count.
					foreach($val['a'] as $answer){
						if(!is_array($high) || $answer['c'] > $high['c'])
							$high = $answer;
					}
					echo $high['a'];
					$spoken = true;
					$response = $val;
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

			//Bot has no answer, try to find another question in topic:
				$highest = false;

				foreach($response['relevantQuestions'] as $k => $question){
					if(is_array($question)){
						if(!$highest || is_array($highest) && $question['c'] > $highest['c']){
							$found = false;
							foreach($questions as $q){
								
								if($q['q'] == $question['q']){
									$found = true;
									$highest = $q;
									break;
								}
							}
							/*if($found){
								$highest = $question;
								break;
							}*/
						}

					}

				}

				if($highest != false){
					//$val['id'] = $id;
					$_SESSION['questions'][0] = $highest;
					echo $highest['q'];
					$spoken = true;
				}

			//Bot still had nothing to say, pick random question.
				if(!$spoken){
					$spoken = true;
					$id = rand(0,count($questions)-1);
					$val = $questions[$id];
					echo $val['q'];
					$val['id'] = $id;
					$_SESSION['questions'][0] = $val;
				}
			
			//rand(count($questions));
		}

		if(!$spoken){
			echo "nevermind";
		}
	} else {
			//Sometimes, ask a random question: even if it spoke
			/*echo "<br>";
			$spoken = true;
			$id = rand(0,count($questions)-1);
			$val = $questions[$id];
			echo $val['q'];
			$val['id'] = $id;
			$_SESSION['questions'][0] = $val;
			*/
			if($response){
				//For each every relevant question..
				$highest = false;

				foreach($response['relevantQuestions'] as $k => $question){
					if(is_array($question)){
						if(!$highest || is_array($highest) && $question['c'] > $highest['c']){
							$found = false;
							foreach($questions as $q){
								
								if(strtolower($q['q']) == strtolower($question['q'])){
									$found = true;
									$highest = $q;
									break;
								}
							}
							/*if($found){
								$highest = $question;
								break;
							}*/
						}

					}

				}

				if($highest != false){
					echo "yay, found it";
					//$val['id'] = $id;
					echo "<br>{$highest['q']}";
					$_SESSION['questions'][0] = $highest;

				} else {
					echo "bleh..".count($val['relevantQuestions']);
				}				
				//echo $val['relevantQuestions'][0]['q'];
			}
	}

	if(!$found){
		$questions[] = array('q'=>$userInput,'a'=>array(),'relevantQuestions'=>array());
		exportDb($questions);
	}
}
function loadDb(){
	$toReturn = false;
	$file = fopen("db.txt","r");
	if($file){
		$toReturn = array();
		$id = 0;
		$lineCount = 0;
		while($line = fgets($file)){
			$questions = array();
			$answers = array();
			$relevantQuestions = array();
			$results = array();
			//echo $lineCount;
			$lineCount++;
			if(preg_match("/(.+);(.*);(.*)$/",$line,$results)){
				$questions = $results[1];
				$answers = $results[2];
				$relevantQuestions = $results[3];
				$as = explode("~",$answers);
				foreach($as as &$answer){
					//$answer = "raz";
					$realmatches;
					$matches = preg_match("/(.+)\(([0-9]+)\)/",$answer,$realmatches);
					if($matches)
					$answer = array('a'=>$realmatches[1],'c'=>$realmatches[2]);
				}
				$rq = explode("~",$relevantQuestions);
				foreach($rq as &$question){
					//$answer = "raz";
					$realmatches;
					$matches = preg_match("/(.+)\(([0-9]+)\)/",$question,$realmatches);
					if($matches){
						$question = array('q'=>$realmatches[1],'c'=>$realmatches[2]);
					}
					
				}
				$relevantQuestions = $rq;
			} else if(!preg_match("/(.+);(.+)/",$line,$results)){
				//Question only present
				$questions = explode(";",$line);
				$questions = $questions[0];
				$answers = array();
				$as = array();
			} else {
				//Question and answers are present
				$questions = $results[1];
				$answers = $results[2];
				$as = explode("~",$answers);
				if(count($as)){
					foreach($as as &$answer){
						//$answer = "raz";
						$realmatches;
						$matches = preg_match("/(.+)\(([0-9]+)\)/",$answer,$realmatches);
						$answer = array('a'=>$realmatches[1],'c'=>$realmatches[2]);
					}
				}
				
			}

			array_push($toReturn, array('id'=>$id,'q'=>$questions,'a'=>$as,'relevantQuestions'=>$relevantQuestions));
			$id++;
			
		}
		fclose($file);
	}
	return $toReturn;

}
//print_r($questions);
function exportDb($array){
	$file = fopen("db.txt","a");
	file_put_contents("db.txt","");
	foreach($array as $line){
		$answers = "";
		if(count($line['a'])){
			$lastval = end(array_values($line['a']));
			foreach($line['a'] as $answer){
				if(isset($answer['c']))
				$answers .= $answer['a']."({$answer['c']})".($answer === $lastval ? "" : "~");
			}
		}
		$relevantQuestions = "";
		/*if(count($line['relevantQuestions']) && is_array($line['relevantQuestions'][0])){
			$lastval = end(array_values($line['relevantQuestions']));
			foreach($line['relevantQuestions'] as $answer){
				$relevantQuestions .= $answer['q']."({$answer['c']})".($answer === $lastval ? "" : "~");
			}
		}*/
		fwrite($file,"{$line['q']};".$answers.";".$relevantQuestions."\n");
	}
	
	fclose($file);
}

?>