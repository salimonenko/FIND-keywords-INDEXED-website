<?php

mb_internal_encoding("utf-8");
$internal_enc = mb_internal_encoding();
mb_regex_encoding($internal_enc);


// 0. Задаем значения опасных параметров по умолчанию (для начала)
$max_keywords_LEN = 0; $reg_keywords = ''; $message_to_user = '';

// 1. Задаваемые параметры/функции
require __DIR__ . '/parametrs.php';
require __DIR__ . '/common_functions.php';


$t0 = microtime(true);
header('Content-type: text/html; charset=utf-8');

// Кнопка запроса очередной порции ссылок
$INPUT_next_links = '<input src="/LOCAL_only/REDACTOR/img/arrow_right.png" style="background-image: none; margin: 10px 0 0 25px; vertical-align: top; width: 30px;" onclick="show_links_in_POPUP(\'links\')" class="buttons_REDACTOR next_links" title="Показать еще '. $links_NUM_max_output .' ссылок" type="image" />';

// 2. find_keywords
if(isset($_POST['find_keywords'])){ // Поиск по ключевым словам (быть может, с учетом логического выражения)
    // 2. Проверяем входные данные
    if(mb_strlen($_POST['find_keywords'], $internal_enc) > $max_keywords_LEN){
        die('<p class="error_mes">Слишком длинный список (логическое выражение) ключевых слов ('. mb_strlen($_POST['find_keywords'], $internal_enc).' символов). А допускается не более '. $max_keywords_LEN. ' символов.</p>');
    }

    if($_POST['find_keywords'] == ''){
        die('<p class="error_mes">Ключевые слова не заданы. Для поиска по ключевым словам следует задать их. Можно использовать логическое выражение на основе  ключевых слов.</p>');
    }

    $keywords = trim($_POST['find_keywords']);

    find_keywords($keywords, $internal_enc, $max_keywords_LEN, $reg_keywords, $message_to_user, $min_WORD_len, $metaphone_len, $path_DIR_name, $file_finded_FILES_name, $links_NUM_max_output, $INPUT_next_links);
}

// 3. ask_keyword_links
if(isset($_POST['ask_keyword_links'])){ // Поиск по ключевым словам (быть может, с учетом логического выражения)

    // 2. Проверяем входные данные
    if(!is_numeric($_POST['ask_keyword_links'])){
        die('<p class="error_mes">Запрос на показ дополнительных ссылок на файлы сайта, содержащие заданные ключевые слова, содержит недопустимые символы.'. '</p>');
    }

    $link_last_num = $_POST['ask_keyword_links']; // Будем брать из файла ссылки, начиная с этого номера

    $file_names_Arr = ask_keyword_links($file_finded_FILES_name, $link_last_num, $links_NUM_max_output);

    $file_names_Arr = array_map(function ($el){
        $href = str_replace('\\', '/', $el);
        return '<li>'. '<a class="files" href="'. $href. '" title="Открыть в новой вкладке" target="_blank"><span class="files">'. $href. '</span></a></li>';
    }, $file_names_Arr); // Превращаем массив имен файлов в массив тегов <li>...</li> с соответствующими ссылками внутри них

    $str =  implode('', $file_names_Arr);

    if($str){
        echo $str;
    }else{
        echo '<p class="error_mes">Больше нет файлов, соответствующих указанным ключевым словам.</p>';
    }
}



/***************      ФУНКЦИИ      **************************/

function ask_keyword_links($file_finded_FILES_name, $link_last_num, $links_NUM_max_output){

    $file_names_Arr = array();

    $input = fopen($file_finded_FILES_name, 'r');
    if($input === false){
        die('<p class="error_mes">Невозможно открыть файл с перечнем имен файлов сайта и их индексами ('. $file_finded_FILES_name. ')</p>');
    }

$i = 0;
    while (($buffer = fgets($input)) !== false){
        if($i++ < $link_last_num){
            continue;
        }

        if($i <= $link_last_num + $links_NUM_max_output){
            $file_names_Arr[] = trim($buffer);
        }else{
            break;
        }

    }
    fclose($input);
return $file_names_Arr;
}


// Функция делает поиск по ключевым словам, в т.ч., с учетом ключевого логического выражения (для этого файлы сайта ДОЛЖНЫ БЫТЬ заранее проиндексированы)
function find_keywords($keywords, $internal_enc, $max_keywords_LEN, $reg_keywords, $message_to_user, $min_WORD_len, $metaphone_len, $path_DIR_name, $file_finded_FILES_name, $links_NUM_max_output, $INPUT_next_links){


$keywords = mb_strtolower($keywords, $internal_enc);

// 0. Проверяем ключевые слова (кл. выражение)
if(preg_match($reg_keywords, $keywords, $matches)){ // Если обнаружится недопустимый символ
    die('<p class="error_mes">В ключевых словах присутствует недопустимый символ: '. implode(' ', $matches).'</p>');
}

if(substr_count($keywords, '(', 0) !== substr_count($keywords, ')', 0)){
    die('<p class="error_mes">К ключевых словах число открывающих скобок "(" равно '. substr_count($keywords, '(', 0). '; это не совпадает с числом закрывающих скобок ")", равным '.substr_count($keywords, ')', 0).'.</p>');
}


// 1. Нормализуем ключевые слова (логическое выражение)
$keywords = preg_replace('/\(\s+/', '(', $keywords); // ( слово  ->  (слово
$keywords = preg_replace('/\s+\)/', ')', $keywords); // слово )  ->   слово)

$keywords = preg_replace('/\s+и\s+/', '&&', $keywords); // и -> &&
$keywords = preg_replace('/\(и\s+/', '(&&', $keywords);
$keywords = preg_replace('/\s+и\)/', '&&)', $keywords);

$keywords = preg_replace('/\s+или\s+/', '||', $keywords); // или -> ||
$keywords = preg_replace('/\(или\s+/', '(||', $keywords);
$keywords = preg_replace('/\s+или\)/', '||)', $keywords);


    $keywords1 = preg_replace('/\s+/', '', $keywords); // Временно удаляем все пробелы
    preg_match_all('/([&\|]{3,})/', $keywords1, $matches); // Нельзя &||  ||&&  и т.д.
    if(sizeof($matches[0]) > 0){ // Нельзя &&&, &&&&, ||| и т.д.
        die('<p class="error_mes">Ключевые слова содержат недопустимые последовательности символов: <b>'. implode(', ', $matches[0]). '</b>. Вместо "&&" в ключевых словах может фигурировать "и", а вместо "||" - "или".</p>');
    }
    preg_match_all('/([^&]&[^&])|([^\|]\|[^\|])/', $keywords1, $matches);
    if(sizeof($matches[0]) > 0){ // Нельзя & или | одиночно. Допускаются только двойные: && или ||
        die('<p class="error_mes">Ключевые слова содержат одиночные символы: <b>&</b> или <b>|</b>. Это недопустимо.</p>');
    }

$keywords = preg_replace('/\s*&+\s*&+\s*/', '&&', $keywords);
$keywords = preg_replace('/\s*\|+\s*\|+\s*/', '||', $keywords);
$keywords = preg_replace('/\s+/', '&&', $keywords); // Все пробелы заменяем на &&

preg_match_all('/(\([&\|]+)|([&\|]+\))|(&\|)|(\|&)|(&{3,})|(\|{3,})/', $keywords, $matches, PREG_PATTERN_ORDER);
if(sizeof($matches[0]) > 0){ // Нельзя (&&  ||)  &&)  ||)
//    print_r($matches);
    die('<p class="error_mes">Ключевые слова содержат недопустимые последовательности символов: <b>'. implode(', ', $matches[0]). '</b>. Вместо "&&" в ключевых словах может фигурировать "и", а вместо "||" - "или".</p>');
}
// Итак, получено логическое выражение вида слово1&&(слово2||слово3)

$special_symb_Arr = array('&&', '||', '(', ')', 1);

// Добавляем пробелы перед и после &&  ||  (  )
for($i=0; $i < sizeof($special_symb_Arr); $i++){
    $keywords = str_replace($special_symb_Arr[$i], ' '. $special_symb_Arr[$i]. ' ', $keywords);
}
$keywords = trim(preg_replace('/\s+/', ' ', $keywords)); // Оставляем только по одному пробелу

$keywords_Arr = explode(' ', $keywords);

// 2. Проверяем корректность полученного логического выражения, временно заменив все кл. слова (метафоны) на 1 (true)
$rez_Arr = check_keywords($keywords_Arr, $special_symb_Arr, $message_to_user, 1);

if($rez_Arr[0] === -1 || $rez_Arr[1] !== true){
    die('<p class="error_mes">Ошибка: выражение с ключевыми словами составлено некорректно, функция eval() не может его оценить.</p>');
}

// 3. Превращаем ключевые слова в метафоны
// Иначе - заменяем слова на 1. Символы && || (  ) оставляем, как есть
$keywords_Arr = array_map('translit1', $keywords_Arr); // В транслит, чтобы можно было применить функцию metaphone()

$keywords_Arr = array_map(function ($el) use ($min_WORD_len, $special_symb_Arr, $internal_enc, $metaphone_len) {
    if(in_array($el, $special_symb_Arr, true)){ // Для && || (  )  1
        return $el;
    }

    return (mb_strlen(($el)) >= $min_WORD_len ? do_metaphone1($internal_enc, $el, $metaphone_len) : 1); // Заменяем короткие слова в логич. выражении на 1 (true)
}, $keywords_Arr);


// 4. Создаем массив, соответствующий ключевому логическому выражению, но где ВСЕ оставш. кл. слова (временно) заменены на 0 (false)
$keywords_FALSE_Arr = array_map(function ($el) use ($min_WORD_len, $special_symb_Arr, $internal_enc, $metaphone_len) {
    if(in_array($el, $special_symb_Arr, true)){ // Для && || (  )  1
        return $el;
    }

    return ($el === 1) ? 1 : 0; // Заменяем все оставшиеся слова на 0 (false). Кроме тех, к-рые установлены равными 1
}, $keywords_Arr);
/*  Для примера поискового логического выражения "время и каталог или мир до" (скобки не использовались) - вот примерный вид массива $keywords_Arr :
        Array
        (
            [0] => frmy
            [1] => &&
            [2] => ktlk
            [3] => ||
            [4] => mir
            [5] => &&
            [6] => 1
        )
*/

// 5. Делаем поиск каждого метафона в каталоге metaphones (среди многочисленных файлов 1.txt)
$indexes_Arr = array(); // Массив строк (взятых из индексных файлов, по одной для каждого слова), 2 начальных символа которых соответствуют последним двум символам метафона.
// Например, для слова "время" (и также "фремя") метафон будет: "frmy". Элемент массива - строка - может примерный вид:  my|;9;10;34;48;50;223;296;323;334;414;439;
// Эти цифры являются индексами файлов сайта (см. файл files.txt)

for($i=0; $i < sizeof($keywords_Arr); $i++){
    if(in_array($keywords_FALSE_Arr[$i], $special_symb_Arr, true)){
        continue; // символы типа && || (  ), а также 1 - не ищем
    }
// 5.1. Создаем путь к файлу 1.txt из каталога metaphones
    $str_to_DIRS = substr($keywords_Arr[$i], 0, -2);
    $str_to_FILE = substr($keywords_Arr[$i], -2);

    $path = realpath($path_DIR_name. '/' .implode('/', str_split($str_to_DIRS)). '/1.txt');

    if(!file_exists($path)){ // Если такого файла нет, значит, такого метафона нет в индексных файлах
        $keywords_FALSE_Arr[$i] = 0;
        continue;
    }else{ // Если такой файл есть
        $file_Arr = explode(PHP_EOL, file_get_contents($path));
// 5.2. Берем только тот элемент массива, который совпадает с 2-мя последними символами метафона
        $elem_Arr = array_filter($file_Arr, function ($el) use ($str_to_FILE){
            return substr($el, 0, 2) === $str_to_FILE;
        });

        if(sizeof($elem_Arr) > 1){
            die('<p class="error_mes">Похоже, ранее произошла ошибка индексирования: в файле '. $path. ' присутствуе БОЛЕЕ ОДНОЙ строки, начинающейся на "'. $str_to_FILE.'. А должно быть НЕ БОЛЕЕ одной строки. Следует исправить программу, при помощи которой ранее производилось индексирование файлов сайта.</p>');
        }elseif(sizeof($elem_Arr) === 0){ // Значит, нет индексов, соответствующих данному метафону
            $keywords_FALSE_Arr[$i] = 0;
        }else{ // Если в индексном файле ровно 1 строчка
            $elem_Arr = array_values($elem_Arr); // Чтобы начальный индекс массива стал равным 0
            $indexes_Arr[$keywords_Arr[$i]] = explode(';', $elem_Arr[0]);
        }


// 5.3. И сразу проверяем, а вдруг при других метафонах, (пока) равных 0, логическое выражение уже будет равно true (это значит, что оно удовлетворяется и дальше можно не искать). Актуально для сложных логических выражений
        $rez_Arr = check_keywords($keywords_FALSE_Arr, $special_symb_Arr, $message_to_user, 0);

        if($rez_Arr[0] === -1){
            die('<p class="error_mes">Ошибка: выражение с ключевыми словами составлено некорректно, функция eval() не может его оценить. Проблема возникла на слове '. $keywords_Arr[$i]. '</p>');
        }
        if($rez_Arr[1]){ // Если логическое выраж. уже равно true, значит, оно удовлетворяется и дальнейший поиск можно не делать (чтобы снизить время поиска)
            // Доделать todo +++
        }
    }

}

// 6. Определяем минимальный и максимальный индексы файлов (приведенных в files.txt), исходя из множеств индексов для каждого из искомых ключевых слов (содержащихся в файлах 1.txt)
$index_MIN = PHP_INT_MAX; $index_MAX = 0;
foreach($indexes_Arr as $item){

    $key = array_search($item, $indexes_Arr);

    $item = array_filter($item, function ($el){
        return is_numeric(trim($el));
    });

    $tmp = min($item);
    if($tmp < $index_MIN){
        $index_MIN = $tmp; // Минимальный индекс среди всех множеств индексов ключевых слов (т.е. для всех ключевых метафонов)
    }
    $tmp = max($item);
    if($tmp > $index_MAX){
        $index_MAX = $tmp; // Максимальный индекс
    }
$indexes_Arr[$key] = $item; // И, попутно, обновляем массив, убирая из его элементов нечисловые (в т.ч. пустые) значения
}


// 7. Определяем такие индексы, что в соответствующих им файлах сайта СОДЕРЖИТСЯ искомое ключевое слово (часть метафона к-рого присутствует в одном из индексных файлов 1.txt)
$TRUE_indexes_Arr = array(); // В этот массив будут собираться индексы файлов сайта, содержимое которых соответствует логическому выражению из метафонов искомых ключевых слов
for($index = $index_MIN; $index <= $index_MAX; $index++){ // По каждому найденному индексу ключевых слов (от мин. до макс.)
    $keywords_Arr_TMP = array();
    for($j=0; $j < sizeof($keywords_Arr); $j++){ // По массиву составных частей логического выражения ключевых слов

        if(in_array($keywords_Arr[$j], $special_symb_Arr, true)){ // Пропускаем такие элементы, как &&  || (  )  1
            $keywords_Arr_TMP[$j] = $keywords_Arr[$j];
            continue;
        }

        if(!isset($indexes_Arr[$keywords_Arr[$j]])){ // Если такого метафона нет, нет и его индексов
            $keywords_Arr_TMP[$j] = 0;
            continue;
        }

        if(in_array($index, $indexes_Arr[$keywords_Arr[$j]], false)){ // false, т.к. нужно НЕстрогое соответствие. Т.к. $index - целое; а $indexes_Arr[$keywords_Arr[$j]] - строка (ибо она получена из файла files.txt)

            // Если данный индекс присутствует в массиве индексов (полученном из соотв. индексного файла 1.txt) для ключевого слова (точнее, метафона),
            $keywords_Arr_TMP[$j] = 1; // то, значит, это ключевое слово СОДЕРЖИТСЯ в файле (сайта), к-рому присвоен этот индекс
        }else{
            $keywords_Arr_TMP[$j] = 0;
        }
    }
// Логическое выражение, с учетом присутствия/отсутствия ключевых слов (в смысле функции metaphone()) в файле сайта, имеющем индекс $index
$bool_expression = implode(' ', $keywords_Arr_TMP);

$rez_Arr = eval_keywords($bool_expression, ''); // Оцениваем его

    if($rez_Arr[0] === -1){
        die('<p class="error_mes">Ошибка: выражение с ключевыми словами составлено некорректно, функция eval() не может его оценить. Проблема возникла на выражении '. $bool_expression.' Вот исходное выражение в метафонах: '. implode(' ', $keywords_Arr). '</p>');
    }
    if($rez_Arr[1]){ // Если оценка логич. выраж. дала true, т.е. метафоны ключевых слов удовлетворяют этому выражению
        $TRUE_indexes_Arr[] = $index; // Собираем такие индексы в массив
    }

}

// 8. Теперь на основе полученных индексов нужно получить имена файлов (из files.txt)
$file_names_Arr = array();

$input = fopen(PATH_FILE_NAMES_ALL_FILES, 'r');
if($input === false){
    die('<p class="error_mes">Невозможно открыть файл с перечнем имен файлов сайта и их индексами ('. PATH_FILE_NAMES_ALL_FILES. ')</p>');
    }

//$t0 = microtime(true);

$i_begin = 0;
$TRUE_indexes_Arr_SIZE = sizeof($TRUE_indexes_Arr);

    if(!$TRUE_indexes_Arr_SIZE){ // Если ни один индекс не соответствует (логическому) ключевому выражению
//        die('<p class="error_mes">Данные ключевые слова не обнаружены ни в одном из индексированных файлов.</p>');
    }


while (($buffer = fgets($input)) !== false) {  // Читаем файл files.txt построчно

    for($i = $i_begin; $i < $TRUE_indexes_Arr_SIZE; $i++){
        $pos = strpos($buffer, '|');
        $TRUE_index = $TRUE_indexes_Arr[$i];

        if(1*trim(substr($buffer, $pos+1)) !== 1*$TRUE_index){ // Если индексы не совпадают
            continue;
        }else{
            $file_names_Arr[] = substr($buffer, 0, $pos);
            $i_begin = $i; // Следующее начало цикла for начинаем не с 0, а с $i, на котором окончился предыдущий цикл for (т.к. предущие значения $i уже нет смысла использовать). Для экономии времени.
// НЕ может быть разных файлов с одинаковым индексом. Поэтому, если уж нашли файл с таким индексом, больше не будет (не должно быть)
            break;
        }
    }


}
fclose($input);

if($TRUE_indexes_Arr_SIZE !== sizeof($file_names_Arr)){ // Значит, или не для всех индексов были найдены файлы из списка в файле files.txt, либо какая-то иная ошибка
    die('<p class="error_mes">Почему-то размерности массива индексов ('. $TRUE_indexes_Arr_SIZE. ') и массива найденных файлов, соответствующих логическому выражению для ключевых слов ('.  sizeof($file_names_Arr) .') НЕ совпадают. Произошла какая-то ошибка. См. Файл '. $_SERVER['PHP_SELF'] . ', стр.'. __LINE__ . '</p>');
}

// 9. Сохраняем найденный перечень файлов в файл
file_put_contents($file_finded_FILES_name, implode(PHP_EOL, $file_names_Arr));

// 10. Оформляем полученные имена файлов для вывода на экран
$output = '<p class="">Всего найдено '. sizeof($file_names_Arr). ' файлов, соответствующим этим ключевым словам. Из них показано <span id="num_showed_links">'. '</span>:';

if(sizeof($file_names_Arr) > $links_NUM_max_output){
    $output .= $INPUT_next_links;
}


$output .= '</p>';


$output .= '<ol id="links">';
$max_links_output_len = min($links_NUM_max_output, sizeof($file_names_Arr));
for($i=0; $i < $max_links_output_len; $i++){

    $href = str_replace('\\', '/', $file_names_Arr[$i]);
    $output .= '<li>'. '<a class="files" href="'. $href. '" title="Открыть в новой вкладке" target="_blank"><span class="files">'. $href. '</span></a></li>';
}
$output .= '</ol>';
echo $output;

}




/*

$t1 = microtime(true);

echo 'Затрачено времени: '. ($t1 - $t0). ' секунд.';

print_r($file_names_Arr);*/


// время и каталог или мир до

/*echo $index_MIN.'!'. $index_MAX;

print_r($TRUE_indexes_Arr);
print_r($keywords_Arr);
print_r($keywords_FALSE_Arr);*/


// 11. На всякий случай, окончательно делаем контроль ошибок
// *************    КОНТРОЛЬ ОШИБОК    (Начало)*****************************************
         if((error_get_last() != '') || (is_array(error_get_last()) && (error_get_last() != array()) )){
             print_r(error_get_last());
             die('<p class="error_mes">Error|Произошла ошибка поиска файлов сайта по ключевым выбранным словам.');
         }
// *************    /КОНТРОЛЬ ОШИБОК    (Конец)*****************************************





function check_keywords($keywords_Arr, $special_symb_Arr, $message_to_user, $bool_val){
    $bool_expression = implode('', array_map(function ($el) use ($special_symb_Arr, $bool_val){
    if(!in_array($el, $special_symb_Arr, true)){
        return $bool_val;
    }else{
        return $el;
    }
}, $keywords_Arr));

$rez_Arr = eval_keywords($bool_expression, $message_to_user);
return $rez_Arr;
}


// Функция оценивает логическое выражение и выдает результат: true или false
function eval_keywords($bool_expression, $message_to_user){
    $bool_expression_REZ = 0; // true, если есть совпадение с выражением для искомых ключевых слов; false - если нет.

    $str_code = "\$bool_expression_REZ = ". $bool_expression;
    @eval($str_code. "|| 1". ";"); // Для проверки корректности выражения $str_code. Если оно верно, результат eval() даст заведомо 1 (true)

    if(!!$bool_expression_REZ){
        eval($str_code. ";"); // Если ошибки не было, получаем фактическое значение

        return array(null, !!$bool_expression_REZ);

    }else{ // Значит, возникла ошибка в выражении для eval()
        return array(-1, $message_to_user);
    }
}




