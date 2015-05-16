##    Mysql Migration Manager

Mysql Migration Manager - программа, написанная на PHP, которая позволяет __автоматически__ создавать миграции 
структуры базы данных и управлять ими.
Вы можете разворачивать созданные другими пользователями миграции на вашей локальной машине либо на удаленном сервере, 
поддерживая таким образом базу в актуальном состоянии. Это полезно в условиях реальной работы множества разработчиков, 
когда постоянно возникает необходимость создания новых таблиц, изменения колонок, добавления триггеров и прочих 
сущностей в БД.
Обратите внимание, что этот способ не связан с созданием миграций в фреймворках, таких как Yii, когда программист сам 
должен описать желаемые правки в структуре данных, а скорее ближе к автоматическому созданию миграций в Doctrine, 
но может покрывать некоторые дополнительные нюансы работы с изменениями структуры данных в MySQL. 

В условиях активной разработки на production-сервере БД может ежедневно вноситься немалое количество изменений. В этом
случае можно рассмотреть возможность выделить ответственного за процесс создания миграций, поручить это DBA или сделать
автоматизированное создание. Затем миграции можно хранить в системе контроля версий, копию файлов из которой каждый 
разработчик сможет получить на своем локальном ПК и развернуть. Для упрощения описания механизма работы введем  
следующие термины:

1. Эталонная БД. Это БД, расположенная на production-сервере. Именно с нее снимается начальный слепок схемы данных, и 
именно к идентичному ей состоянию должна быть приведена структура БД на локальных машинах разработчиков
2. Временная БД. Это БД, расположенная на локальной машине лица, создающего новые миграции, либо на локальной машине 
разработчика или в целом любого окружения, где необходимо, не имея точных сведений о состоянии локальной БД, привести 
ее к состоянию, идентичному эталонной БД. Временная БД может иметь статичное имя, либо ее может генерироваться на 
основе текущего времени и префикса
3. MMM - непосредственно сокращение от Mysql Migration Manager. Фактически эта программа является фронтендом к форку 
[mysqldiff](https://github.com/vitaliy-evsyukov/mysqldiff), программы на Perl. Об ее установке будет рассказано ниже

Таким образом, процесс работы может быть построен следующим образом:

- автоматически или вручную снимается миграция, в это время *никаких* операций со структурой данных в БД не должно 
происходить, равно как и на втором этапе
- для проверки корректности миграции следут сразу же после снятия повторить попытку создания, если создастся еще одна
миграция, это скажет о том, что обнаружен дефект в mysqldiff, который не позволил создать миграцию с таким содержимым, 
что его однократное применение приведет к идентичности БД на production-сервере и на сервере, где создается временная 
БД, используемая для сравнения с эталонной
- на машине разработчика либо на машине, где требуется развернуть БД с идентичной эталонной БД структурой, выполняется 
очередная полученная миграция или форсированное приведение к эталонной структуре

Обращаю ваше внимание, что:

- команда upgrade в большинстве случаев должна выполняться однократно, после разворачивания дампа БД, который могут 
предоставить для начальной работы, либо в том случае, если отсутствует или утеряна информация о последней примененной 
миграции
- команда migrate должна выполняться каждый раз, когда получена новая миграция или их набор (если в течение некоторого 
периода обновления не проводились, но информация о последней примененной миграции сохранена)
- команда deploy должна выполняться только если стоит задача разворачивания тестовой БД с данными
(для отладки или для создания некоторой песочницы)
- команда init должна выполняться только на тех данных, которые не представляют ценности, так как удаляет все таблицы 
из базы и создает пустые таблицы из схемы

### Настройка окружения

#### БД

Для работы c МММ удобнее всего создать нового пользователя БД. Для упрощения настройки вы можете выдать ему 
все привилегии. Однако, если необходимо, чтобы пользователь имел только реально необходимые ему полномочия, вот они:

- Для основой БД, с которой будут сниматься миграции и/или на которую они будут накладываться: 
`CREATE, DROP, LOCK TABLES, ALTER, DELETE, INDEX, INSERT, UPDATE, SELECT, UPDATE, TRIGGER, 
CREATE VIEW, SHOW VIEW, ALTER ROUTINE, CREATE ROUTINE, EXECUTE, SHOW DATABASES`
- Для БД mysql: право `SELECT` на `mysql.proc`
- Для временной БД: все привилегии

В примере далее будем считать, что мы устанавливаем все полномочия. Также примем за данность, что наша локальная база
называется `main_db`, эталонная - `production_main_db`, а временная БД - `mmm_tmp`.

Выполните на локальном сервере БД следующие операторы (вместо pass следует использовать более безопасный пароль):

```sql
CREATE USER 'mmm-user'@'%' IDENTIFIED BY 'pass';
GRANT ALL PRIVILEGES ON mmm_tmp.* TO 'mmm-user'@'%';
GRANT ALL PRIVILEGES ON main_db.* TO 'mmm-user'@'%';
GRANT SELECT ON mysq.proc TO 'mmm-user'@'%';
```

На боевом сервере БД выполните следуюшие операторы:

```sql
CREATE USER 'mmm-user'@'%' IDENTIFIED BY 'pass';
GRANT SELECT, LOCK TABLES, EXECUTE, SHOW VIEW, 
      TRIGGER ON production_main_db.* TO 'mmm-user'@'%'
GRANT SELECT ON mysq.proc TO 'mmm-user'@'%';
```

В процессе работы MMM может появиться ошибка

    ERROR 1419 (HY000): You do not have the SUPER privilege and
    binary logging is enabled (you *might* want to use the less safe
    log_bin_trust_function_creators variable)
    
В этом случае выполните команду

```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```

#### mysqldiff

Программа mysqldiff написана на Perl, репозиторий с ней является подмодулем MMM. Вы можете самостоятельно склонировать 
его с помощью команды 

`git clone https://github.com/vitaliy-evsyukov/mysqldiff`

Однако более простым способом будет получение непосредственно подмодуля. В папке MMM выполните команды

    git submodule init
    git submodule update
    
В комплекте есть скрипты установки для Windows, Debian-like систем и MacOS. 

##### Установка для *nix

Ввиду некорректных изменений 
в процессе апгрейда от Fedora 15 до Fedora 16 (или в случае возникновения подобных проблем под другими ОС) можно 
использовать, отредактировав, файл install_fedora.sh. В нем параметр -I должен иметь значение, равное пути, по которому 
интерпретатор perl должен искать библиотеки. В моем случае я сталкивался со следующей проблемой:

    perl: symbol lookup error: /usr/local/lib/perl5/auto/version/vxs/vxs.so: undefined symbol: Perl_Gthr_key_ptr

В общем случае для установки необходимы:

- perl >= 5.006
- Module::Build
- Carp
- File::Slurp
- IO::File
- DBI
- DBD::mysql

Они должны установиться автоматически скриптом установщика, однако в случае возникновения проблем можно попробовать 
сделать это вручную с помощью команд:

    $ sudo cpan Module::Build && cpan Carp && cpan File::Slurp && cpan IO::File && cpan DBI && cpan DBD::mysql
    $ sudo apt-get install libmysqlclient-dev
    
Автоматическая установка для Debian-like систем производится путем запуска скрипта `install_windows_debian.sh`
    
Для установки при наличии Percona Server на Fedora вместо "штатного" mysql необходимо установить пакет 
Percona-Server-devel с помощью команды

    $ yum install Percona-Server-devel-55
    
##### Установка для Windows

Для установки под Windows (тестировалось на Windows 7 Ultimate x64) необходимо скачать пакет 
[ActivePerl](http://www.activestate.com/activeperl/downloads). Даже если у вас 64-разрядная ОС, необходимо установить 
x86 версию, поскольку на момент написания не существует способа установить MinGW "из коробки" для версии под 
64-разрядные ОС.После установки откройте Perl Package Manager, выберите в меню View пункт All Packages, найдите в 
списке и установите MinGW (для этого нужно щелкнуть правой кнопкой по MinGW, выбрать единственный пункт меню и 
выполнить `File -> Run marked actions...`). Затем перейдите в папку с исходными кодами mysqldiff и выполните команду 

    perl Build.PL
     
Если вы получите сообщение, что требуются зависимости, выполните 

    perl Build installdeps 
    
и затем снова 

    perl Build.PL
    
После этого выполните 

    perl Build
    perl Build install.

По умолчанию mysqldiff установится как `C:\Perl\site\bin\mysqldiff`.

##### Установка для MacOS

Используя штатный Perl:

    sudo cpan install Module::Build
    sudo cpan install Carp
    sudo cpan install File::Slurp
    sudo cpan install IO::File
    sudo cpan install DBI
    sudo cpan install DBD::mysql
    
Использя Brew: так же, но без `sudo`.

