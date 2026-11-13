<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType;

use Concrete\Core\File\Service\File;
use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper;

class FieldTypeList
{
    /**
     * @var array
     */
    protected static $fieldTypes = [];

    /**
     * @var array
     */
    protected static $packages = [];

    /**
     * @var array
     */
    protected static $pkgVersionsRequired = [];

    /**
     * @return array
     */
    public static function getFieldTypes()
    {
        if (empty(self::$fieldTypes)) {
            $results = [];
            $pkgBd = Package::getByHandle('block_developer');
            $pkgBdVersion = $pkgBd->getPackageVersion();
            $packages = Package::getInstalledList();
            foreach ($packages as $pkg) {
                $packageHandle = $pkg->getPackageHandle();
                $pkgDirectory = self::getPackageFolder($packageHandle);
                $directory = $pkgDirectory . 'src' . DIRECTORY_SEPARATOR . 'BlockDeveloper' . DIRECTORY_SEPARATOR . 'FieldType';
                // Check to see if we have the BlockDeveloper/FieldType directory for this package
                if (is_dir($directory)) {
                    $pkgVersion = $pkg->getPackageVersion();
                    self::$packages[$packageHandle] = [
                        'version' => $pkgVersion,
                    ];
                    $folders = self::getDirList($directory);
                    if (array_key_exists($packageHandle, self::$pkgVersionsRequired)) {
                        $neededVersion = self::$pkgVersionsRequired[$packageHandle];
                        if (!version_compare($pkgVersion, $neededVersion, '>=')) {
                            continue;
                        }
                    }
                    // We need folders of field types, within the FieldType directory
                    if (!empty($folders)) {
                        foreach ($folders as $folder) {
                            $ftDirectory = $directory . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
                            $ftClassName = BlockDeveloper::_nameSpace($folder);
                            $ftClassFile = $ftDirectory . $ftClassName . '.php';
                            // We need the file to exists, before we actually do other stuff
                            if (file_exists($ftClassFile)) {
                                // Retrieve the full namespace from the file
                                $ftNamespace = self::getClassFromFile($ftClassFile);
                                /* @var $ftClass FieldType
                                 * we extend this "FieldType" class from its own package, it should exist of course */
                                if (trim($ftNamespace) != '' && class_exists($ftNamespace)) {
                                    $ftClass = new $ftNamespace($ftDirectory, $packageHandle, $pkgDirectory, $ftClassName);
                                    // We first check if we haven't got this Field Type already in our results
                                    if (!isset($results[$ftClassName])) {
                                        $pkgVersionRequired = $ftClass->getPkgVersionRequired();
                                        $appVersionRequired = $ftClass->getAppVersionRequired();
                                        if (($pkgVersionRequired === false || version_compare($pkgVersionRequired, $pkgBdVersion, '<=')) && ($appVersionRequired === false || version_compare($appVersionRequired, self::getConcreteVersionInstalled(), '<='))) {
                                            $results[$ftClassName] = [
                                                'name' => $ftClass->getFieldTypeName(),
                                                'description' => $ftClass->getFieldTypeDescription(),
                                                'namespace' => $ftNamespace,
                                                'directory' => $ftDirectory,
                                                'pkgHandle' => $packageHandle,
                                                'pkgDirectory' => $pkgDirectory,
                                                'className' => $ftClassName,
                                                'class' => $ftClass,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            self::$fieldTypes = $results;
        }

        return self::$fieldTypes;
    }

    /**
     * @param $handle
     *
     * @return bool|mixed
     */
    public static function getFieldTypeByHandle($handle)
    {
        return isset(self::$fieldTypes[$handle]) ? self::$fieldTypes[$handle] : false;
    }

    /**
     * @param $handle
     *
     * @return bool
     */
    public static function getFieldTypeClassByHandle($handle)
    {
        if ($type = self::getFieldTypeByHandle($handle)) {
            $fieldType = new $type['namespace']($type['directory'], $type['pkgHandle'], $type['pkgDirectory'], $type['className']);

            return $fieldType;
        }

        return false;
    }

    /**
     * @param $d
     *
     * @return array
     */
    private static function getDirList($d)
    {
        $l = [];
        $directories = (new File())->getDirectoryContents($d);
        foreach ($directories as $directory) {
            if (is_dir($d . '/' . $directory)) {
                $l[] = $directory;
            }
        }
        sort($l);

        return $l;
    }

    /**
     * @param $file
     *
     * @return mixed|string
     */
    private static function getClassFromFile($file)
    {
        $contents = (new File())->getContents($file);
        $namespace = $class = '';
        $gettingNamespace = $gettingClass = false;
        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $gettingNamespace = true;
            }
            if (is_array($token) && $token[0] == T_CLASS) {
                $gettingClass = true;
            }
            if ($gettingNamespace === true) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespace .= $token[1];
                } elseif ($token === ';') {
                    $gettingNamespace = false;
                }
            }
            if ($gettingClass === true) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        return $namespace ? $namespace . '\\' . $class : "Devoda\BlockDeveloper\BlockDeveloper\FieldType\\{$class}\\{$class}";
    }

    /**
     * @param $pkgHandle
     *
     * @return string
     */
    public static function getPackageFolder($pkgHandle)
    {
        return DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . $pkgHandle . DIRECTORY_SEPARATOR;
    }

    public static function getConcreteVersionInstalled()
    {
        return Config::get('concrete.version_installed');
    }
}
