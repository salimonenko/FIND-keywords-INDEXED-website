// Функция запускается после получения ответа сервера (по AJAX), обрабатывает ответы сервера
    function Function_after_server(arg_Arr, responseTEXT, responseSTATUS) {

// 1. Задаем общие переменные
        var idd_ol = 'links'; // id списка, в к-рый записываются приходящие с сервера ссылки на файлы сайта, соответствующие логич. выраж. с ключевыми словами
        var id_num_showed_links = 'num_showed_links'; // id тега, к-рый показывает общее число таких ссылок на странице

// 2. Выполняем действия, в зависимости от запросов на сервер
        if(arg_Arr[0] === 'Notepad_PP'){ // Если был запрос на запуск Notepad_PP
            document.getElementById('responser').innerHTML += responseTEXT;
        }

        if(arg_Arr[0] === 'ask_string_number'){ // Если был запрос к серверу для определения номера строки в файле files.txt

            var path_file_names_all_files = '/unknown/';
            if(document.querySelector('[data-path_file_names_all_files]')){ // Читаем атрибут из тега ЭТОГО скрипта
                path_file_names_all_files = document.querySelector('[data-path_file_names_all_files]').getAttribute('data-path_file_names_all_files');
            }

            if(responseTEXT === '-1'){
                document.getElementById('responser').innerHTML += 'Похоже, произошла ошибка сервера: невозможно открыть файл <?php echo PATH_FILE_NAMES_ALL_FILES; ?>';
            }else{
                document.getElementById('last_managed_string').value = responseTEXT;

                if(responseTEXT === document.getElementById('last_index').textContent){
                    var path_file_names_error = '/unknown/';
                    if(document.querySelector('[data-path_file_names_error]')){ // Читаем атрибут из тега ЭТОГО скрипта
                        path_file_names_error = document.querySelector('[data-path_file_names_error]').getAttribute('data-path_file_names_error');
                    }

                    alert('Это число равняется номеру последней строки в файле '+ path_file_names_all_files + '. Похоже, все файлы проиндексированы. Чтобы убедиться в этом, посмотрите также файл '+ path_file_names_error + ', в котором могут содержаться относительные пути и индексы файлов, индексирование которых не получилось.\n\nЧтобы убедиться дополнительно, выполните операцию "Показать номера НЕпроиндексированных строк"');
                }else {
                    document.getElementById('responser').innerHTML += 'Установлен номер строки, равный '+ responseTEXT+ '. После обновления страницы индексирование файлов будет продолжено, начиная с этого номера строки.<br/>';
                }
            }
        }

        if(arg_Arr[0] === 'save_string_number'){
            document.getElementById('responser').innerHTML += responseTEXT;
        }

        if(arg_Arr[0] === 'ask_string_numbers_NOT_indexed'){
            document.getElementById('rezults').innerHTML = responseTEXT;
        }

        if(arg_Arr[0] === 'DO_working'){
            if(responseTEXT === 'true'){ // Сигнал о том, что создан флаг-файл (при наличии которого может проводиться индексирование). Обновляем страницу для начала индексирования
                window.location.reload();
            }
            else if(responseTEXT === 'false'){ // Сигнал о том, что флаг-файл удален

                document.getElementById('responser').innerHTML += '<p class="info_mes">Флаг-файл успешно удален. Процесс индексирования остановлен.</p>';
            }else if(responseTEXT === 'Error_unlink_flag_FILE'){ // Сигнал о том, что не получлиось удалить флаг-файл
                document.getElementById('responser').innerHTML += '<p class="error_mes">НЕ получилось удалить флаг-файл. Чтобы остановить процесс индексирования, повторите операцию еще раз...</p>';
            }else {
                document.getElementById('responser').innerHTML += '<p class="info_mes">' + responseTEXT + '</p>';
            }
        }

        if(arg_Arr[0] === 'find_keywords'){ // Если был запрос на поиск ключевых слов (логич. выражения) в индексированных файлах
            document.getElementById('popup1').innerHTML = responseTEXT;

            if(document.getElementById(idd_ol)){
                document.getElementById(id_num_showed_links).textContent = document.getElementById(idd_ol).querySelectorAll('a.files').length; // Показываем, сколько фактически ссылок на файлы пришло с сервера
            }else{
                console.log('Вот ответ сервера: ' + responseTEXT);
//            alert('Похоже, возникла ошибка! Для уточнения проблемы см. также сообщение в консоли.');
            }
        }

        if(arg_Arr[0] === 'ask_keyword_links'){ // Если был запрос на получение еще скольких-то ссылок на файлы, содержащих искомые слова

            if(document.getElementById(idd_ol)){
                document.getElementById(idd_ol).innerHTML += responseTEXT; // Добавляем ссылки в список

                document.getElementById(id_num_showed_links).textContent = document.getElementById(idd_ol).getElementsByTagName('li').length; // Обновляем общее число ссылок
// Удаляем все кнопки запроса для поиска следующей порции ссылок, оставляем только самую первую
                var next_links = document.getElementById('popup1').getElementsByClassName('next_links');
                for (var i=1; i < next_links.length; i++){
                    var parent = next_links[i].parentNode;
                    parent.removeChild(next_links[i]);
                }

                var div = document.createElement('div'); // Фиктивный блок
                div.innerHTML = responseTEXT;

                if(div.querySelector('a.files')){
// Если в ответе сервера есть хотя бы одна ссылка с классом files (т.е. ссылка на файл, удовл. логич. условию поиска кл. слов)
                    var INPUT_next_links_new = next_links[0].cloneNode(true);
                    document.getElementById('popup1').appendChild(INPUT_next_links_new); // Добавляем еще одну  кнопку сразу за списком
                }

            }else{
                alert('Ошибка: на странице отсутствует элемент с id="'+ idd_ol + '". Поэтому невозможно показать ссылки, полученные с сервера.');
            }
        }

        if(arg_Arr[0] === 'file_FINDER'){ // Если был запрос на создание файла files.txt. Это - файл со строками вида:  \filename|560
            if(document.getElementById('rezults')){
                document.getElementById('rezults').innerHTML = responseTEXT;
            }else{ // Если такого блока нет, то выводим ответ сервера хоть куда-нибудь
                document.body.innerHTML += responseTEXT;
                console.log(responseTEXT);
                alert('Т.к. на странице отсутствует блок для получения ответа сервера, ответ выведен в конец страницы, а также продублирован в консоли. Обновите эту страницу.');
            }
        }

        if(arg_Arr[0] === 'Notepad_PP_error_log'){ // Если был запрос на открытие файла лога ошибок
            document.getElementById('responser').innerHTML += responseTEXT;
        }

    }

// Функция непосредственно выполняет запрос на сервер
function DO_send_data1(Function_after1, arg_Arr, url_php, body) {
    /*  Function_after1 - функция, запускаемая после прихода ответа сервера
     arg_Arr - массив параметров, передаваемый при запуске функции Function_after1
     url_php - URL, куда направляется запрос (сообщение)
     body - тело сообщения на сервер
     */
    var xhr = new XMLHttpRequest();
    xhr.open("POST",  url_php, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function xhr_state() {
        if (xhr.readyState != 4) return;
        if (xhr.status >= 200 && xhr.status <= 400) {
            // Принят ответ сервера
            var responseTEXT = xhr.responseText;
            var responseSTATUS = xhr.status;

            if (responseTEXT == "") {
                alert("Ошибка сервера: получен пустой ответ.")
            }

            if(arg_Arr != '' && Function_after1 != ''){ // Выполняем функцию с именем Function_after1, если ее имя и массив параметров - не пустые
//                        var func = new Function('return ' + Function_after1)();
//                    func.call(window.document, request, scriptBODY);
                Function_after1(arg_Arr, responseTEXT, responseSTATUS); // Вызывается после получения ответа сервера
            }

        } else {alert('xhr error 2\nВозможно, сервер недоступен\n' + xhr.statusText +'\n\n Проверьте, есть ли у Вас в данный момент связь с сервером, на котором находится эта программа.\n Если (локальный) сервер точно запущен - подождите пару минут, пока обновится кэш IP-адресов браузера. Или - обновите страницу.' );}
    };

    xhr.send(body); // запрос
}


// Функция запускает индексирование ИМЕН ВСЕХ файлов сайта (за исключением запрещенных к индексации).
function file_FINDER() {

    if(document.getElementById('rezults')){
        document.getElementById('rezults').innerHTML = '';
    }

    var body = 'file_FINDER='+null + '&r=' + Math.random();
    var arg_Arr = ['file_FINDER'];
    var url_php = 'file_FINDER.php';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}


