<?php

namespace lib;

class helpController extends AbstractController {

    public function runStrategy() {
        printf(file_get_contents(DIR . 'tpl/help.tpl')."\n");
    }

}
