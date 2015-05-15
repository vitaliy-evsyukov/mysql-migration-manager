<?php

namespace lib\Helper;

use lib\AbstractSchema;

/**
 * Filesystem
 * Хелпер для работы с ФС
 * @author  Виталий Евсюков
 * @package lib\Helper
 */
class Filesystem extends Helper
{
    /**
     * @var bool
     */
    private $prepareStandardDirectories = false;

    /**
     * Список директорий для итерации
     * @var array
     */
    private $iteratingDirectories = [];

    /**
     * Массив с действиями, которые уже запросили ввода пользователя
     * Необходим для того, чтобы в цепочках не повторять вопросы
     * @var array
     */
    private $executedRequests = array();

    /**
     * Набор строк, описывающих ревизии
     * @var array
     */
    private $revisionsLines = array();

    /**
     * Счетчик ревизий, отображает текущую
     * @var int
     */
    private $currentRevision = -1;

    /**
     * Последняя найденная в списке ревизия
     * @var int
     */
    private $lastRevision = 0;

    /**
     * Содержимое файлов датасетов
     * @var array
     */
    private $datasets = [];

    /**
     * Создает, если не было, директорию для миграций
     * @param array|string $dirs Список директорий. Если не указан, создаются и проверяются стандартные
     * @return void
     */
    public function initDirs($dirs = array())
    {
        if (!is_array($dirs)) {
            $dirs = array($dirs);
        }
        if (empty($dirs)) {
            // папки по умолчанию
            if (!$this->prepareStandardDirectories) {
                $dirs       = array('savedir', 'cachedir', 'schemadir');
                $namespaces = array(
                    'savedir'  => SAVEDIR_NS,
                    'cachedir' => CACHEDIR_NS
                );
                foreach ($dirs as &$dir) {
                    if (isset($namespaces[$dir])) {
                        $this->set("{$dir}_ns", $namespaces[$dir]);
                    }
                    $this->set($dir, DIR . $this->get($dir) . DIR_SEP);
                }
                $this->prepareStandardDirectories = true;
            }
        }
        foreach ($dirs as $dir) {
            if ($this->exists($dir)) {
                $dirname = $this->get($dir);
            } else {
                $dirname = $dir;
                $dir     = basename(rtrim($dir, '/'));
            }
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
                $this->output(
                    sprintf('Created %s directory in path: %s', $dir, $dirname),
                    3
                );
            }
        }
    }

    /**
     * Утилитарная работа с получением содержимого шаблона и заменой
     * @static
     * @param array  $search  Массив местозаменителей
     * @param array  $replace Массив соответствующих им замен
     * @param string $tpl     Имя файла шаблона
     * @return string
     * @throws \Exception
     */
    private function createContent(array $search, array $replace, $tpl)
    {
        $tpl_file = DIR . $tpl;
        if (is_file($tpl_file) && is_readable($tpl_file)) {
            $content = file_get_contents($tpl_file);
        } else {
            throw new \Exception(
                sprintf(
                    'Template file %s not exists or is not readable',
                    $tpl_file
                )
            );
        }
        foreach ($search as &$placeholder) {
            $placeholder = "%%{$placeholder}%%";
        }

        return str_replace($search, $replace, $content);
    }

    /**
     * Получить номер ожидаемой ревизии
     * @return int
     */
    public function getLastRevision()
    {
        if (!$this->lastRevision) {
            $this->getAllMigrations();
        }

        return ++$this->lastRevision;
    }

    /**
     * Получает номер текущей ревизии
     * @return int
     */
    public function getCurrentRevision()
    {
        if ($this->currentRevision === -1) {
            $this->getAllMigrations();
        }

        return $this->currentRevision;
    }

    /**
     * Получает список строк ревизий
     * @return array
     */
    public function getRevisionsLines()
    {
        if (empty($this->revisionsLines)) {
            $this->getAllMigrations();
        }

        return $this->revisionsLines;
    }

    /**
     * Записывает информацию о ревизии
     * @param int $revision Номер ревизии
     * @throws \Exception
     * @return int Таймстаймп для ревизии
     */
    public function writeRevisionFile($revision)
    {
        $path      = $this->get('savedir');
        $filename  = $path . $this->get('versionfile');
        $marker    = $path . $this->get('version_marker');
        $timestamp = time();
        $lines     = $this->getRevisionsLines();
        $exists    = ($revision === 0);
        foreach ($lines as $line) {
            $data = explode('|', $line);
            if ((int) $data[0] === $revision) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $lines[] = sprintf(
                "%d|%s|%d",
                $revision,
                date('d.m.Y H:i:s', $timestamp),
                $timestamp
            );
            if (is_file($filename) && !is_writable($filename)) {
                throw new \Exception(
                    sprintf(
                        "File %s is write-protected",
                        $filename
                    )
                );
            }
            file_put_contents($filename, implode("\n", $lines));
        }
        $this->revisionsLines = $lines;
        if (is_file($marker) && !is_writable($marker)) {
            throw new \Exception(
                sprintf(
                    'Cannot write revision marker to file: %s',
                    $marker
                )
            );
        }
        file_put_contents($marker, "#{$revision}");

        return $timestamp;
    }

    /**
     * Записывает данные в указанный файл
     * @param string    $filename
     * @param IWritable $writable
     * @throws \Exception
     */
    public function writeInFile($filename, IWritable $writable)
    {
        $search   = $writable->getPlaceholders();
        $replace  = $writable->getReplacements($this->container->getInit());
        $template = $writable->getTemplateName();
        if (!file_exists($filename) || is_writable($filename)) {
            $this->output(sprintf('Write file %s', $filename), 3);
            file_put_contents(
                $filename,
                $this->createContent($search, $replace, $template)
            );
        }
    }

    /**
     * Сохраняет ответ в списке
     * @param string $answer Название вопроса (ключ)
     * @param string $value  Ответ пользователя (по умолчанию "1")
     */
    private function saveAnswer($answer, $value = '1')
    {
        $this->executedRequests[$answer] = $value;
    }

    /**
     * Получить сохраненный ответ пользователя
     * @param string $answer Название вопроса (ключ)
     * @return mixed|bool Ответ пользователя, по умолчанию false
     */
    private function getAnswer($answer)
    {
        $value = false;
        isset($this->executedRequests[$answer]) && ($value = $this->executedRequests[$answer]);
        return $value;
    }

    /**
     * Спрашивает у пользователя, необходимо ли перезаписывать файл
     * @param string   $filename Имя файла
     * @param string   $message  Сообщение
     * @param callable $fn       Функция, использумая для дополнительной проверки
     * @param array    $fnArgs   Аргументы функции проверки
     * @return boolean Результат ввода пользователя
     */
    public function askToRewrite($filename, $message = '', $fn = null, $fnArgs = array())
    {
        $tagged  = null;
        $virtual = false;
        if (is_array($filename)) {
            $tagged   = isset($filename['tag']) ? $filename['tag'] : null;
            $virtual  = isset($filename['virtual']) ? (bool) $filename['virtual'] : false;
            $filename = $filename['file'];
        }
        $hash = md5($filename);
        if ($this->getAnswer($hash)) {
            // если уже отвечали, нет смысла запрашивать ввод пользователя
            return false;
        } else {
            if ($this->get('quiet') || (!$virtual && !file_exists($filename))) {
                $this->saveAnswer($hash, 'y');
                $c = true;
            } else {
                $choices = [
                    'y' => true,
                    'n' => false
                ];
                if (!is_null($fn)) {
                    $c = call_user_func_array($fn, $fnArgs);
                    $this->saveAnswer($hash, array_search($c, $choices, true));
                } else {
                    $variants = ['yes', 'no'];
                    $entered  = '';
                    if (!is_null($tagged)) {
                        $taggedAnswer = $this->getAnswer($tagged);
                        $variants     = array_merge($variants, ['Yes to all', 'No to all']);
                        $choices      = array_merge($choices, ['Y' => 'y', 'N' => 'n']);
                        $this->output(sprintf('Fetched "%s" choice for tag %s', $taggedAnswer, $tagged), 3);
                    } else {
                        $taggedAnswer = false;
                    }
                    do {
                        if (!$taggedAnswer) {
                            if ($entered !== "\n") {
                                if (empty($message)) {
                                    $message = 'File %s already exists. Do you really want to override it? [%s] ';
                                }
                                printf($message, $filename, implode('/', $variants));
                            }
                            $entered = fgets(STDIN);
                            $c       = trim($entered);
                            if (!$tagged) {
                                $c = mb_strtolower($c);
                            }
                        } else {
                            $c = $taggedAnswer;
                        }
                        if (isset($choices[$c])) {
                            $choice = $c;
                            $c      = $choices[$c];
                            /**
                             * Если выбрали вариант Y или N, то сохраним фактически true или false
                             */
                            if ($tagged && isset($choices[$c])) {
                                if (!$taggedAnswer) {
                                    $this->saveAnswer($tagged, $choice);
                                    $this->output(sprintf('Saved %s choice for tag %s', $choice, $tagged), 3);
                                }
                                $c = $choices[$c];
                            }
                            $this->saveAnswer($hash, $c);
                            break;
                        }
                    } while (true);
                }
            }

            return $c;
        }
    }

    /**
     * Возвращает имя файла схемы (или имя "по умолчанию", даже если файла не существует)
     * @param string $hash       Хеш от датасетов
     * @param string $schemaType По умолчанию должна возвращаться "мигрированная" схема или "обычная"
     * @throws \Exception
     * @return string
     */
    public function getSchemaFile($hash = '', $schemaType = null)
    {
        $path         = $this->get('cachedir');
        $pattern      = '%sSchema%s%s.class.php';
        $files        = array(
            AbstractSchema::MIGRATED => sprintf($pattern, $path, $hash, 'migrated'),
            AbstractSchema::ORIGINAL => sprintf($pattern, $path, $hash, '')
        );
        $index        = -1;
        $needMigrated = ($schemaType === AbstractSchema::MIGRATED);
        foreach ($files as $key => $file) {
            // если файл существует
            if (is_file($file)) {
                $index = $key;
                $this->output('Founded schema file ' . $file, 2);
            }
            // если файл найден или нужна только мигрированная схема, выходим из цикла
            if ((!$schemaType && ($index !== -1)) || ($index === $schemaType) || $needMigrated) {
                break;
            }
        }

        if ($index === -1) {
            $index = $needMigrated ? AbstractSchema::MIGRATED : AbstractSchema::ORIGINAL;
        }

        $file = $files[$index];

        if ($needMigrated) {
            unset($this->executedRequests[md5($file)]);
        }

        // и доступен на чтение и запись, вернем его
        if (is_file($file) && (!is_readable($file) || !is_writable($file))) {
            throw new \Exception(sprintf('Cannot get schema file (tried %s, but has not RW access', $file));
        }

        return $file;
    }

    /**
     * Возвращает имя файла с описанием связей таблиц
     * @param string $hash
     * @param string $path
     * @return string
     */
    public function getReferencesFile($hash, $path)
    {
        return sprintf('%sReferences%s.class.php', $path, $hash);
    }

    /**
     * Возвращает список всех миграций и связанных с ними данных
     * @throws \Exception
     * @return array
     */
    public function getAllMigrations()
    {
        $this->revisionsLines  = [];
        $this->currentRevision = -1;
        $migrationsDir         = $this->get('savedir') . '/';
        $migrationsListFile    = $migrationsDir . $this->get('versionfile');
        $markerFile            = $migrationsDir . $this->get('version_marker');
        $result                = array(
            'migrations' => array(),
            'data'       => array()
        );
        if (is_file($markerFile) && is_readable($markerFile)) {
            $handler = fopen($markerFile, 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if (empty($line)) {
                        continue;
                    }
                    if ($line[0] === '#') {
                        $this->currentRevision = (int) substr($line, 1);
                        break;
                    }
                }
                fclose($handler);
            }
        }
        if (is_file($migrationsListFile) && is_readable($migrationsListFile)) {
            $handler = fopen($migrationsListFile, 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if (empty($line)) {
                        continue;
                    }
                    $this->revisionsLines[]       = $line;
                    $parts                        = explode('|', $line);
                    $migrationId                  = (int) $parts[0];
                    $time                         = (int) $parts[2];
                    $result['migrations'][]       = $migrationId;
                    $result['data'][$migrationId] = array(
                        'date' => $parts[1],
                        'time' => $time,
                        'revn' => $migrationId
                    );
                    $result['timestamps'][$time]  = $migrationId;
                    $this->lastRevision           = $migrationId;
                }
                fclose($handler);
            } else {
                throw new \Exception(sprintf("Failed to open file %s", $migrationsListFile));
            }
        }
        if ($this->currentRevision === -1) {
            $this->currentRevision = $this->lastRevision;
        }
        usort(
            $result['migrations'],
            function ($a, $b) use ($result) {
                return ($result['data'][$a]['time'] >
                    $result['data'][$b]['time']) ? 1 : -1;
            }
        );

        return $result;
    }

    /**
     * Возвращает массив файлов в текущей итерируемой папке, или false, если больше нет папок для итераций
     * @param array $dirs
     * @return array|bool
     */
    public function iterateDirectories(array $dirs)
    {
        if (empty($this->iteratingDirectories)) {
            $this->iteratingDirectories = [
                'listing' => $dirs,
                'working' => getcwd()
            ];
        } else {
            $dirs = $this->iteratingDirectories['listing'];
        }
        $result = false;
        if (!empty($dirs)) {
            $result = [];
            $dir    = array_pop($dirs);
            $this->output(sprintf('Come into %s directory', $dir), 3);
            $handle = opendir($dir);
            chdir($dir);
            $dirLen = mb_strlen($dir);
            if (mb_substr($dir, $dirLen - 1, 1) !== DIR_SEP) {
                $dir .= DIR_SEP;
            }
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..' && is_readable($file)) {
                    if (is_file($file)) {
                        $result[] = $file;
                    } elseif (is_dir($file)) {
                        /**
                         * Если это директория, то допишем ее имя к строке
                         * поддиректорий и добавим в стек директорий
                         */
                        $dirToAdd = $dir . $file;
                        array_push($dirs, $dirToAdd);
                        $this->output(
                            sprintf('Add subdirectory %s', $dirToAdd),
                            3
                        );
                    }
                }
            }
            closedir($handle);
            $this->iteratingDirectories['listing'] = $dirs;
        } else {
            if (isset($this->iteratingDirectories['working'])) {
                // возвращаем назад рабочую директорию
                chdir($this->iteratingDirectories['working']);
            }
            $this->iteratingDirectories = [];
        }
        return $result;
    }

    /**
     * Возвращает массив с данными по датасетам
     * Массив состоит из ключей:
     * <pre>
     * reqs -------
     *            |- dataset1 -> JSON1
     *            |- dataset2 -> JSON2
     * sqlContent -
     *            |- dataset1 -> SQL1
     *            |- dataset2 -> SQL2
     * </pre>
     * @param array $datasets           Массив имен датасетов
     * @param bool  $loadDatasetContent Загружать ли содержимое SQL датасетов
     * @return array
     * @throws \Exception
     */
    public function getDatasetsInfo(array $datasets, $loadDatasetContent = false)
    {
        if (empty($this->datasets)) {
            $dsdir = DIR . $this->get('datasetsdir');
            // получить данные
            if (!is_dir($dsdir) || !is_readable($dsdir)) {
                throw new \Exception(
                    sprintf(
                        "Directory %s with datasets is not exists",
                        $dsdir
                    )
                );
            }

            $handle = opendir($dsdir);
            chdir($dsdir);

            while ($dir = readdir($handle)) {
                // если в хеше датасетов такой есть, то начнем читать папку с ним
                if (isset($datasets[$dir]) && is_dir($dir) && is_readable($dir)) {
                    $tablesFileName = $dir . DIR_SEP . $this->get('reqtables');
                    if (is_file($tablesFileName) && is_readable($tablesFileName)) {
                        $this->datasets['reqs'][$dir] = json_decode(
                            file_get_contents($tablesFileName),
                            true
                        );
                        $datafile                     = $dir . DIR_SEP . $this->get('reqdata');
                        if ($loadDatasetContent && is_file($datafile) && is_readable($datafile)) {
                            $this->datasets['sqlContent'][$dir] = file_get_contents($datafile);
                        }
                    }
                }
            }

            closedir($handle);
            if (empty($this->datasets) || ($loadDatasetContent && empty($this->datasets['sqlContent']))) {
                throw new \Exception('Data for deploy not found');
            }
        }

        return $this->datasets;
    }


    /**
     * Возвращает название временной папки, очищает ее, если она существует
     * @param string $tmpDir Завершающая часть пути к временной папке
     * @return string
     */
    public function getTempDir($tmpDir)
    {
        $tmpDir = sprintf('%s/%s/', sys_get_temp_dir(), $tmpDir);
        if (is_dir($tmpDir)) {
            /**
             * Удаление вложенных папок и файлов и затем удаление директории
             */
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                } elseif ($file->isDir()) {
                    rmdir($file->getPathname());
                } elseif ($file->isFile() || $file->isLink()) {
                    unlink($file->getPathname());
                }
            }
            rmdir($tmpDir);
        }
        return $tmpDir;
    }
}