<?php

namespace yzh52521\GridCaptcha;

class Install
{
    const WEBMAN_PLUGIN = true;

    /**
     * @var array
     */
    protected static $pathRelation = array(
        'config/plugin/yzh52521/gridcaptcha' => 'config/plugin/yzh52521/gridcaptcha',
    );

    /**
     * Install
     * @return void
     */
    public static function install()
    {
        $resources = __DIR__ . '/resources';
        $storage   = __DIR__ . '/storage';
        self::copyFile($resources, base_path());
        self::copyFile($storage, public_path() . '/storage');
        static::installByRelation();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
    }

    /**
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            if ($pos = strrpos($dest, '/')) {
                $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }
            //symlink(__DIR__ . "/$source", base_path()."/$dest");
            copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
        }
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            $path = base_path() . "/$dest";
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }
            /*if (is_link($path) {
                unlink($path);
            }*/
            remove_dir($path);
        }
    }

    protected static function copyFile($source, $destination, $child = 1)
    {
        if (!is_dir($source)) {
            echo("Error:the $source is not a direction!");
            return 0;
        }
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0777) && !is_dir($destination)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $destination));
            }
        }
        $handle = dir($source);
        while ($entry = $handle->read()) {
            if (($entry !== ".") && ($entry !== "..")) {
                if (is_dir($source . "/" . $entry)) {
                    if ($child) {
                        self::copyFile($source . "/" . $entry, $destination . "/" . $entry, $child);
                    }
                } else {
                    copy($source . "/" . $entry, $destination . "/" . $entry);
                }
            }
        }
    }

}
