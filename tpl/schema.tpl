<?php

namespace db;

use lib\AbstractSchema;

class Schema%%name%% extends AbstractSchema {

    protected $tables = array(
        %%tables%%
    );
    protected $queries=array(
        %%queries%%
    );

}
