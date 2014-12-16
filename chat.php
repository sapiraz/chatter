<?php
session_start();
$_SESSION["questions"] = isset($_SESSION['questions']) && is_array($_SESSION['questions']) ? $_SESSION['questions'] : array();
$userInput = isset($_POST['text']) ? $_POST['text'] : "";
$questions = array();
$file = fopen("db.txt",'r');
if($file){
	while(($line = fgets($file))){
		$raw = explode(";",$line);
		$qs = explode(",",$raw[0]);
		$raw[1] = trim($raw[1]);
		$as = explode(",",$raw[1]);
		
		array_push($questions, array('q'=>$qs,'a'=>$as));
	}
}
fclose($file);

$res = false;
$spoken = false;
if(is_array($_SESSION['questions']) && count($_SESSION['questions']) >= 1){
	if($userInput == ""){
	echo "I asked you a question..";
	echo $_SESSION['questions'][0];
	} else {
		$file = fopen("db.txt","r");
		$i = 0;
		$lineToEdit = false;
		while(($line = fgets($file))){
			$raw = explode(";",$line);
			$qs = explode(",",$raw[0]);
			foreach($qs as $ques){
				if(strtolower($ques) == strtolower($_SESSION['questions'][0])){
					$lineToEdit = $i;
					break;
				}
			}
			if($lineToEdit != false){
				break;
			}
			$i++;
		}
		$lines = file( "db.txt" , FILE_IGNORE_NEW_LINES );
		$f = explode(";",$lines[$lineToEdit]);
		$lines[$lineToEdit] = $f[0].";".$userInput.",".preg_replace("/\n+/","",$f[1]);
		file_put_contents( "db.txt" , implode( "\n", $lines ) );
		unset($_SESSION['questions'][0]);
		foreach($questions as $q => $val){
			
			foreach($val['q'] as $a){
				if(strtolower($a) == strtolower($userInput)){
					$res = true;
					if(count($val['a'])){
						$tospeak = $val['a'][0];
						if(strlen(trim($tospeak)) >= 1){
							echo $tospeak;
							$spoken = true;
						}
					}
						
					break;
				}
				if($res){
					break;
				}
			}

		}
		if(!$spoken){
			echo "Oh okay..";
		}
		
	}

} else if($userInput != "") {

	foreach($questions as $q => $val){
		
		foreach($val['q'] as $a){
			if(strtolower($a) == strtolower($userInput)){
				$res = true;
				if(count($val['a'])){
					$tospeak = $val['a'][0];
					if(strlen(trim($tospeak)) >= 1){
						echo $tospeak;
						$spoken = true;
					}
				}
					
				break;
			}
			if($res){
				break;
			}
		}

	}
	if(!$spoken){
		echo "I don't know what to say.. but....<br>";
		foreach($questions as $q => $val){
			if(count($val['a']) == 1 && $val['a'][0] == ""){
				echo $val['q'][0];
				$_SESSION['questions'][0] = $val['q'][0];
				break;
			}
		}
	}

	if(!$res){
		$file = fopen("db.txt","a");
		fwrite($file,"\n{$userInput};");
		fclose($file);
	}
}
?>