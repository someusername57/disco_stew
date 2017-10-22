<?php


function work(){
    global $db;
    if (!isset($_POST['word'])){
        return false;
    }
    
    for ($i = 0; $i < count($_POST['word']); $i++){
        $res = $db -> query("SELECT * FROM words WHERE word='".$db ->real_escape_string($_POST['word'][$i])."'");
        
        $cats = array();
        for ($j = 0; $j < 7; $j++){
            if (isset($_POST['category_'.$j])){
                $cats[] = $j;
            }
        }
        
        if ($res -> num_rows == 0) {
            $db -> query("INSERT INTO words VALUES(null, '".$db ->real_escape_string($_POST['word'][$i])."', '".$db ->real_escape_string($_POST['quality'][$i])."', '".$db ->real_escape_string(implode(',', $cats))."', '".$db ->real_escape_string($_POST['neibor_words'][$i])."')");
        } else {
            $old = $res -> fetch_object();
            $neibors = explode(',', $old -> neibors);
            $new_neibors = explode(',', $_POST['neibor_words'][$i]);
            foreach ($new_neibors as $value) {
                if (!in_array($value, $neibors)){
                    $neibors[] = $value;
                }
            }
            
            $categs = explode(',', $old -> category);
            foreach ($cats as $value) {
                if (!in_array($value, $categs)){
                    $categs[] = $value;
                }
            }
            
            $new_qulity = $old -> quality;
            if ($_POST['quality'][$i] != ''){
                if ($_POST['quality'][$i] > $old -> quality) {
                    $new_qulity = ceil(($_POST['quality'][$i] + $old -> quality)/2);
                } else {
                    $new_qulity = floor(($_POST['quality'][$i] + $old -> quality)/2);
                }
            }
            
            $db -> query("UPDATE words SET category='".$db ->real_escape_string(implode(',',$categs))."' ,quality='$new_qulity', neibors='".$db ->real_escape_string(implode(',',$neibors))."' WHERE id='{$old -> id}'");
        }
    }
    
}


$last = (file_exists('last_checked')) ? file_get_contents('last_checked') : 0;

$db = new mysqli("localhost", 'root', 'super_pass', 'hackaton2017');
$db->set_charset("utf8");







if (isset($_POST['subm'])){
    work();
    
    $last = intval($last) +1;
    file_put_contents('last_checked', $last);
    
}
$res = $db -> query("SELECT * FROM comm LIMIT $last, 1");

$row = $res -> fetch_object() -> tid;

//$row = iconv('cp1251', 'utf-8', $row);


?>
<html>
    <head>
        <meta charset="utf-8">
        <title>AZAZZAZAZA</title>
    </head>
    <body>
        <?php
        
        $words = array();
        preg_match_all('/\b[а-яА-Я]*\b/ui', $row, $words);
        
        $words = $words[0];
        foreach ($words as $key => $value) {
            if (trim($value) == ''){
                unset($words[$key]);
            } else {
                $words[$key] = trim(strtolower($words[$key]));
            }
            
        }
        
        $words = array_values($words);
        
        
        ?>
        <style>
            textarea {
                width: 200px;
                height: 100px;
            }
            #text > span{
                cursor: pointer;
            }
        </style>
        <div id="text">
            <?php
            foreach ($words as $value) {
                echo '<span onclick="add_to(this)">'.$value.'</span> ';
            }
            ?>
        </div>
        <form method="post">
            <table border="1">
                <tr>
                    <td>Слово</td><td>Качество</td><td>Категория</td><td>Соседние слова</td>
                </tr>
<?php

            
            
        
?>
            </table>
            <input type="button" onclick="add()" value="add" />
            <input type="submit" value="Send" name="subm">
        </form>
        <script>
            function add(){
                var current = document.querySelectorAll('table tr').length - 1;
                document.querySelector('table tbody').innerHTML +='<tr>\n\
                            <td>\n\
                                <input class="word" type="radio" name="current" />\n\
                                <input name="word['+current+']" type="text">\n\
                            </td>\n\
                            <td>\n\
                                <input name="quality['+current+']" type="text">\n\
                            </td>\n\
                            <td>\n\
                                <div><input type="checkbox" name="category_0['+current+']">Гнев</div>\
                                <div><input type="checkbox" name="category_1['+current+']">Ненависть</div>\
                                <div><input type="checkbox" name="category_2['+current+']">Агрессия</div>\
                                <div><input type="checkbox" name="category_3['+current+']">Оскорбления</div>\
                                <div><input type="checkbox" name="category_4['+current+']">Раздражение</div>\
                                <div><input type="checkbox" name="category_5['+current+']">Угрозы</div>\
                                <div><input type="checkbox" name="category_6['+current+']">Негативизм</div>\
                            </td>\n\
                            <td>\n\
                                <input type="radio" class="neibors" name="current" />\n\
                                <textarea name="neibor_words[]"></textarea>\n\
                            </td><td><input onclick="remove(this)" type="button" value="remove"></td></tr>';
            }
            
            function remove(self){
                self.parentNode.parentNode.parentNode.removeChild(self.parentNode.parentNode);
            }
            
            function add_to(self){
                var text = self.innerHTML;
                var el = document.querySelector('input[type="radio"]:checked');
                if (el.classList.contains('word')){
                    el.nextElementSibling.value = text;
                } else {
                    el.nextElementSibling.value += (el.nextElementSibling.value == '') ? text : ',' + text;
                }
            }
        </script>
    </body>
</html>



