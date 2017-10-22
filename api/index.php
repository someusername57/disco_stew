<?php
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	//header('Content-Type: application/json');
	header("Access-Control-Allow-Origin: *");

if (!defined('INCLUDED')){ macke_magick(''); }

function macke_magick($str){
	$db = new mysqli("localhost", 'root', 'super_pass', 'hackaton2017');
	$db->set_charset("utf8");

	$categorys = array(
		"Гнев",
		"Ненависть",
		"Агрессия",
		"Оскорбления",
		"Раздражение",
		"Угрозы",
		"Негативизм"
	);

	$return = new stdClass();
	if (!defined('INCLUDED')){
		if(!isset($_REQUEST['q']))
		{
			$return->error = "unvalid";
			print json_encode($return);
			die();
		}
		else if(empty(trim($_REQUEST['q'])))
		{
			$return->error = "unvalid";
			print json_encode($return);
			die();
		}
	} else {
		
		$_REQUEST['q'] = $str;
	}
	
	$not_check = array('я','ко','а','э', 'и', 'ну', "ты", "вы", "мы", "твоя", "твою", "твои", "тебе", "тебя");
	$to_del_if_found_bad = array('попробуй','э', 'и', 'ну', "ты", "вы", "мы", "твоя", "твои", "тебе", "тебя", 'меня', 'семью', 'всю', 'твою', 'это', 'какая');
	// $msg = 'Ублюдок, мать твою, а ну иди сюда, говно собачье! Что, решил ко мне лезть?! Ты, засранец вонючий, мать твою, а? Ну, иди сюда, попробуй меня трахнуть, я тебя сам трахну, ублюдок, онанист чертов, будь ты проклят! Иди, идиот, трахать тебя и всю твою семью, говно собачье, жлоб вонючий, дерьмо, сука, падла! Иди сюда, мерзавец, негодяй, гад, иди сюда, ты, говно, ЖОПА!';
	//$msg = 'Олежа ну ты блин и жирная скотина, как так можно кушать. как ведь так.';
	//$msg = 'Ты жирная скотина, где макет?';
	// $msg ='Ебать лентач это какая хуита, раньше новости были лучше, а щяс хуйня.';
	$msg = $_REQUEST['q'];

	$splits = array(",", ".", "-", '!', '?');

	$parts = multisplit($msg, $splits);

	$i = 0;

	$global_bad = 0;
	$global_ok = 0;
	$bad_in_cat = array();

foreach ($parts as &$part) {

    if (!isset($part -> need_clear)){
        $part -> need_clear = false;
    }
    do {
		// print $part -> text . '<br><br>';
        $work_again = false;
        $words = find_words($part -> text);
        
		$counter = 0;
        foreach ($words as $key => $word) {
			
            if (in_array(mb_strtolower($word), $not_check)){
                continue;
            }
            $global_ok++;
			
            $res = $db -> query("SELECT * FROM words WHERE LOWER(word) LIKE LOWER('%".$word."%')");
            $counter++;
			
            if ($res -> num_rows == 0) {
                continue ;
            }
			
//             
            while($res_row = $res -> fetch_object()){
                $phrase = $res_row -> word;
                if (preg_match('/\b'.$phrase.'\b/ui', $part -> text)){
					
					
					$cats = explode(",", $res_row -> category);
					
					foreach($cats as $cat)
					{
						if(!isset($bad_in_cat[$cat]))
							$bad_in_cat[$cat] = 1;
						else $bad_in_cat[$cat]++;
					}
					// $global_ok--;
					$global_bad++;
//            		
					//echo $word.' | '.$res_row->category."\n";
					// echo $phrase . ' -> ' .  $res_row -> category . '<br>';
                    $part -> text = preg_replace('/'.$phrase.'/ui', '', $part -> text);
                    $part -> need_clear = true;
                    $work_again = true;
                }
            }
//            print_r($part);
//            sleep(1);
//          
        }
		
		// if(count($bad_in_cat) > 0)
		// {
			// print '>> ' . $counter . '<br>';
			// foreach($bad_in_cat as $bad => $value)
			// {
				// print $categorys[$bad] . ' -> ' . round($value / $counter, 2) . '<br>';
			// }
			// print '<hr>';
		// }

        
    } while ($work_again);
    
    if ($part -> need_clear){
        foreach ($to_del_if_found_bad as $value){
            $part -> text = preg_replace('/\b'.$value.'\b'.'/ui', '', $part -> text);
        }
        
        $part -> text = preg_replace("/ {2,}/ui"," ",$part -> text);
    }
    //echo 'cleared part:'."\n";
    //print_r($part);
    
}

$total = ($global_ok != 0) ? round($global_bad / $global_ok, 2) : 0;
// print $total . '<br>';
$resp['total'] = $total;
//23 как 100%, делим их по количеству категорий + 
$sum = 0;
foreach($bad_in_cat as $bad => $value)
	$sum+=$value;

$check = 0.0;
$resp['detail'] = array();
foreach($bad_in_cat as $bad => $value)
{
	$check += (round($total * (round($value / $sum, 3) * 100), 1) / 100);

	$resp['detail'][mb_strtolower($categorys[$bad])] =  (round($total * (round($value / $sum, 3) * 100), 1));
}

krsort($resp['detail']);

//ДИМА!
// print $check . '<br>';
// print $total . '<br>';
if( $check == $total )
	$resp['check'] = true;
else
	$resp['check'] = false;

$restored = multirestore($parts);

foreach($splits as $value){
    $restored = str_replace(' '.$value, $value, $restored);
}

$resp['result'] = sentence_case(mb_strtolower($restored));
if (!defined('INCLUDED')){
echo json_encode($resp);
} else {
	return json_encode($resp);
}
}
// echo $resp['result'];
function multirestore($parts){
    $return = '';
    // echo '<pre>';
    
    foreach ($parts as $part){
        
        if (trim($part -> text) == ''){
            continue;
        }
        // var_dump($part);
        $return .= trim($part -> text).$part -> del.' ';
    }
    // echo '</pre>';
    return $return;
}



function multisplit($s, $split){
    $r = array();
    $c = 0;
    $temp = new stdClass();
    $temp->text = "";
    $temp->del = "";

    for($i=0; $i<strlen($s); $i++) {
        if(in_array($s[$i], $split)) {
            $temp->del .=  $s[$i];

            if(mb_strlen(trim($temp->text)) > 0) $r[] = $temp;

            $temp = new stdClass();
            $temp->text = "";
            $temp->del = "";
        } else {
            $temp->text .=  $s[$i];
        }
    }
    
    if(!empty($temp->text)) $r[] = $temp;
    
    return $r;
}

function find_words($row){
    $words = array();
    preg_match_all('/\b[а-яА-Яa-zA-Z]*\b/ui', $row, $words);

    $words = $words[0];
    foreach ($words as $key => $value) {
        if (trim($value) == ''){
            unset($words[$key]);
        } else {
            $words[$key] = trim(strtolower($words[$key]));
        }

    }

    $words = array_values($words);
    return $words;
}
//if (!function_exists("mb_substr_replace")) 
//{
    function mb_substr_replace($string, $replacement, $start, $length=null, $encoding=null) {
        if ($encoding == null) $encoding = mb_internal_encoding();
        if ($length == null) {
            return mb_substr($string, 0, $start, $encoding) . $replacement;
        }
        else {
            if($length < 0) $length = mb_strlen($string, $encoding) - $start + $length;
            return 
                mb_substr($string, 0, $start, $encoding) .
                $replacement .
                mb_substr($string, $start + $length, mb_strlen($string, $encoding), $encoding);
        }
    }
//}
    

function mb_ucfirst($string, $enc = 'UTF-8')
{
    return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
        mb_substr($string, 1, mb_strlen($string, $enc), $enc);
}
    
function sentence_case($string) { 
    $sentences = preg_split('/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE); 
    $new_string = ''; 
    foreach ($sentences as $key => $sentence) { 
        $new_string .= ($key & 1) == 0? 
            mb_ucfirst(strtolower(trim($sentence))) : 
            $sentence.' '; 
    } 
    return trim($new_string); 
}