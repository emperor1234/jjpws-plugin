<?php

namespace JJPWS\Core;

class Deactivator {

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
