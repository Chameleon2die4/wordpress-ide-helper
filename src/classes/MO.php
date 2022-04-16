<?php /** @noinspection SpellCheckingInspection */

class MO
{

    /**
     * @param string $mofile
     * @return bool
     */
    public function import_from_file($mofile) {
        return is_string($mofile);
    }

    public function merge_with($domain)
    {
    }

}