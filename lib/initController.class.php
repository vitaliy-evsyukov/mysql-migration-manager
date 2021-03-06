<?php

namespace lib;

/**
 * initController
 * Приводит содержимое схемы к начальному
 * @author Виталий Евсюков
 */
class initController extends DatasetsController
{
    public function runStrategy()
    {
        $filesystem = $this->container->getFileSystem();
        $configure  = $this->container->getInit();
        if ($filesystem->askToRewrite(
            ['file' => 'ALL tables', 'virtual' => true],
            'Are you really sure you want to delete %s in DB [%s] '
        )
        ) {
            $datasets = $this->args['datasets'];
            $dshash   = '';
            if (!empty($datasets)) {
                ksort($datasets);
                $dshash = md5(implode('', array_keys($datasets)));
            }
            try {
                $this->dropAllDBEntities();
                $classname = $this->container->getSchema()->getSchemaClassName($dshash, false);
                /**
                 * @var AbstractSchema $schema
                 */
                $schema = new $classname;
                $schema->load($this->db, $this->container);
                $this->verbose(
                    sprintf(
                        "Schema %s was successfully deployed",
                        $classname
                    ),
                    1
                );
                $filesystem->writeRevisionFile(0);
            } catch (\Exception $e) {
                $this->verbose('Schema not found', 1);
                $this->verbose($e->getMessage(), 3);
                /**
                 * @var schemaController $schema
                 */
                $schema = $configure->getController('schema');
                $schema->runStrategy();
            }
        } else {
            $this->verbose("Exit without any changes", 1);
        }
    }
}
