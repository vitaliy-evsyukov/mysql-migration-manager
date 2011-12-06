                       Mysql Migration Manager
---------------------------------------------------------------------
Использование:
    ./migration.php [опции конфигурации] команда [аргументы команды]

Список доступных команд:

    help:                           Отобразить эту подсказку
    schema:                         Создать схему данных и развернуть ее
    init:                           Откатиться к схеме данных
    create:                         Создать миграцию
    list:                           Отобразить список доступных миграций
    migrate:                        Совершить миграцию
    deploy:                         Создать и/или развернуть схему данных, совершить миграции и применить наборы данных
    applyds:                        Применить наборы данных
    recover:                        Восстановить файл, указанный в конфигурации как значение параметра versionfile
  
Опции конфигурации могут задаваться двумя способами:

    --config                        Путь до альтернативного файла конфигурации, в противном случае будет использован файл config.ini из основной директории
    ЛИБО:
    --<опция>=<значение>
    Список опций и доступные им значения такие же, как в конфигурационном файле:
    --host                          Имя хоста сервера БД
    --user                          Имя пользователя
    --password                      Пароль
    --db                            Имя БД
    --savedir                       Путь к директории, в которой будут сохраняться файлы миграций
    --cachedir                      Путь к директории, в которой будут сохраняться файлы схем
    --datasetsdir                   Путь к директории с наборами данных
    --schemadir                     Путь к директории с начальными описаниями таблиц
    --reqtables                     Файл в формате JSON, в котором хранится список нужных для набора данных таблиц и (в будущем) дополнительные параметры набора данных
    --reqdata                       Файл с SQL-командами
    --versionfile                   Файл, в котором будет храниться информация о миграциях
    --mysqldiff_command             Команда для запуска mysqldiff
    --verbose                       Уровень отладки. Отладочные сообщения будут выводиться, только если он больше либо равен уровню, с которым выводится отладочное сообщение.
    --quiet                         Отключает вывод всех сообщений, за исключением ошибок

Список доступных аргументов команд:
    --datasets="набор1,набор2"      Список наборов данных (через запятую). Если опущен, используются все наборы.
    --m="время"                     Время, до которого нужно мигрировать. Допускает отрицательные значения. Если опущено, то используется текущее. Если передано число, то считается номером миграции.
    --revision="номер"              Считать текущей версией не ту, которая указана в файле versionfile, а собственную. Используется только командой migrate

Уровни отладки:
    1                               Выводятся сообщения, отражающие общий ход выполнения программы. Значение по умолчанию.
    2                               Выводятся сообщения, которые могут быть полезны программистам и связаны с кодом программы.
    3                               Выводится техническая информация, такая как время выполнения операторов и запросов
 
Для указания миграции вы можете использовать такой же формат, как принимает функция strtotime
Примеры:
*********************************************************************
./migration.php migrate --m="yesterday"
./migration.php migrate --m="-2 hour"
./migration.php migrate --m="+2 month"
./migration.php migrate --m="20 September 2001"
./migration.php migrate
********************************************************************


---------------------------------------------------------------------
Лицензия: GPL v3
Автор: Guy Fawkes <geserx@gmail.com>