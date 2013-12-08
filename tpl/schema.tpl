<?php

namespace %%ns%%;

use lib\AbstractSchema;

class Schema%%name%% extends AbstractSchema {

    protected $_tables = array(
        %%tables%%
    );

    protected $_revision = %%revision%%;

    protected $_queries = %%queries%%;

}
