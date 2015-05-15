<?php

/**
 * getsqlController
 * Получить описания всех таблиц
 * @author Виталий Евсюков
 */

namespace lib;

class getsqlController extends DatasetsController
{

    public function runStrategy()
    {
        $entities = array('TABLE', 'VIEW', 'PROCEDURE', 'FUNCTION', 'TRIGGER');
        /**
         * ops - ключ операторов для показа списка
         * links - какой оператор нужен для показа сущностей какого типа
         * cols
         * - ключ list для сущности показывает, где находится ее имя
         * - ключ def показывает, в каком столбце ее описание
         */
        $operations = array(
            'ops'   => array(
                "SHOW FULL TABLES WHERE Table_type LIKE '%%%s'",
                'SHOW %sS',
                "SHOW %s STATUS WHERE Db='" . $this->db->getDatabaseName() . "'"
            ),
            'links' => array(
                'TABLE'     => 0,
                'VIEW'      => 0,
                'TRIGGER'   => 1,
                'FUNCTION'  => 2,
                'PROCEDURE' => 2
            ),
            'cols'  => array(
                'TABLE'     => array(
                    'list' => 0,
                    'def'  => 1
                ),
                'VIEW'      => array(
                    'list' => 0,
                    'def'  => 1
                ),
                'TRIGGER'   => array(
                    'list' => 'Trigger',
                    'def'  => 'SQL Original Statement'
                ),
                'PROCEDURE' => array(
                    'list' => 'Name',
                    'def'  => 'Create Procedure'
                ),
                'FUNCTION'  => array(
                    'list' => 'Name',
                    'def'  => 'Create Function'
                )
            )
        );
        $opts       = array();
        foreach ($this->args as $arg) {
            if (is_string($arg)) {
                $parts = explode('=', $arg);
                if (sizeof($parts) != 2) {
                    $parts[1] = '1';
                }
                $opts[strtoupper(trim($parts[0], '-'))] = $parts[1];
            }
        }
        $hFS     = $this->container->getFileSystem();
        $hDB     = $this->container->getDb();
        $hSchema = $this->container->getSchema();
        $path    = $hFS->get('schemadir');
        $suffix  = md5(time());
        $hDB->setCurrentDb($this->db, 'Get SQL');
        foreach ($entities as $entity) {
            $op     = sprintf(
                $operations['ops'][$operations['links'][$entity]],
                $entity
            );
            $eLower = strtolower($entity);
            $hFS->initDirs(sprintf('%s%ss', $path, $eLower));
            $this->verbose(sprintf('Receiving list of %ss', $eLower), 1);
            $res = $this->db->query($op);
            if (empty($res)) {
                throw new \Exception(
                    sprintf(
                        'Cannot fetch list of %ss. Try to change your privileges. Error is %s',
                        $eLower,
                        $this->db->getLastError()
                    )
                );
            }
            while ($row = $res->fetch_array(MYSQLI_BOTH)) {
                // имя сущности
                $col = $row[$operations['cols'][$entity]['list']];
                // столбец, где находится ее описание
                $value = $operations['cols'][$entity]['def'];
                if (!empty($opts[$entity])) {
                    // если имя не подходит под регулярное выражение, пропустим
                    if (!preg_match('/^' . $opts[$entity] . '/', $col)) {
                        continue;
                    }
                }
                $this->verbose(
                    sprintf(
                        'Get %s %s description',
                        $eLower,
                        $col
                    ),
                    1
                );
                $q    = "SHOW CREATE {$entity} {$col}";
                $desc = $this->db->query($q);
                if (empty($desc)) {
                    throw new \Exception(
                        sprintf(
                            'Cannot view definition of %s called %s. Error is %s',
                            $entity,
                            $col,
                            $this->db->getLastError()
                        )
                    );
                }
                $data = $desc->fetch_array(MYSQLI_BOTH);
                if (isset($data[$value])) {
                    $filename = sprintf('%s%ss/%s.sql', $path, $eLower, $col);
                    if (!$hFS->askToRewrite(['file' => $filename, 'tag' => $suffix])) {
                        $filename .= $suffix;
                    }
                    $data[$value] .= str_repeat(
                        ';',
                        (int) (!in_array($entity, array('TABLE', 'VIEW'))) + 1
                    );
                    $data[$value] = $hSchema->stripTrash(
                        $data[$value],
                        $entity,
                        array('entity' => $col)
                    );
                    if ($entity === 'VIEW') {
                        /**
                         * Получим описания полей вьюхи и создадим временную таблицу с таким же именем
                         */
                        $q         = "SHOW FIELDS FROM {$col}";
                        $fieldsRes = $this->db->query($q);
                        if (empty($fieldsRes)) {
                            throw new \Exception(
                                sprintf(
                                    'Cannot fetch fields for view called %s. Error is %s',
                                    $col,
                                    $this->db->getLastError()
                                )
                            );
                        } else {
                            $fields = array();
                            while ($fieldsRow = $fieldsRes->fetch_array(MYSQLI_BOTH)) {
                                $fields[] = sprintf('%s %s', $fieldsRow['Field'], $fieldsRow['Type']);
                            }
                            $tempTable = sprintf('CREATE TABLE %s (%s);', $col, implode(', ', $fields));
                            file_put_contents(sprintf('%stables/%s.sql', $path, $col), $tempTable);
                            $this->verbose(
                                sprintf(
                                    'Temporary table structure for view %s created',
                                    $col
                                ),
                                1
                            );
                        }
                    }
                    file_put_contents($filename, $data[$value]);
                } else {
                    $this->verbose(
                        sprintf(
                            'Cannot to get description of %s %s',
                            $eLower,
                            $col
                        ),
                        1
                    );
                }
            }
        }
        $this->verbose('Files successfully created', 1);
    }
}