<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Database\Schema\Schema;
use Concrete\Core\Package\PackageList;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Support\Facade\Facade;
use Doctrine\DBAL\Schema\Comparator;
use Environment;
use ORM;

class BlockDeveloper
{
    /**
     * @var array
     */
    protected static $packages = [];

    /**
     * @var array
     */
    protected static $pkgVersionsRequired = [];

    /**
     * @var array
     */
    protected static $fieldSlugsBlacklist = ['limit', 'description', 'select', 'file', 'bid', 'bttable', 'helpers', 'btfieldsrequired', 'btinterfacewidth', 'btinterfaceheight', 'btcacheblockrecord', 'btcacheblockoutput', 'btcacheblockoutputonpost', 'btcacheblockoutputforregisteredusers', 'btcacheblockoutputlifetime', 'bthandle', 'btname', 'btexportpagecolumns', 'btexportfilecolumns', 'btexportpagetypecolumns', 'btexportpagefeedcolumns', 'btwrapperclass', 'btignorepagethemegridframeworkcontainer', 'pkg', 'asc', 'desc', 'option', 'fulltext', 'bigint', 'int', 'smallint', 'tinyint', 'bit', 'decimal', 'numeric', 'float', 'real', 'smalldatetime', 'char', 'varchar', 'nchar', 'nvarchar', 'ntext', 'binary', 'varbinary', 'column', 'condition', 'double', 'where', 'tabs', 'interval', 'app'];

    /**
     * @param $name
     *
     * @return string
     */
    public static function _nameSpace($name)
    {
        $nameSpaced = implode('', array_map(function ($v, $k) {
            return ucfirst($v);
        }, explode('_', $name), array_keys(explode('_', $name))));

        return $nameSpaced;
    }

    /**
     * @return array
     */
    public static function getFieldSlugsBlackList()
    {
        return self::$fieldSlugsBlacklist;
    }

    /**
     * @param      $btHandle
     * @param bool $pkgHandle
     *
     * @return bool|mixed
     */
    public static function getBlockTypeController($btHandle, $pkgHandle = false)
    {
        $app = Facade::getFacadeApplication();
        if (!$pkgHandle) {
            $pkgHandle = self::getBlockTypePackageHandle($btHandle);
        }
        if ($class = BlockType::getBlockTypeMappedClass($btHandle, $pkgHandle)) {
            if (class_exists($class)) {
                return $app->build($class);
            }
        }

        return false;
    }

    public static function clearOverrideCache()
    {
        $legacy = version_compare(Config::get('concrete.version'), '8.2', '<');
        if (!$legacy) {
            $app = Facade::getFacadeApplication();
            $cache = $app->make('cache/overrides');
            $cache->flush();
        } else {
            $cacheFile = Config::get('concrete.cache.directory') . '/' . Config::get('concrete.cache.environment.file');
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }

    /**
     * @param $btHandle
     *
     * @return bool|string
     */
    public static function getBlockTypePackageHandle($btHandle)
    {
        $pkgHandle = false;
        $db = Facade::getFacadeApplication()->make('database')->connection();
        if ($bt = $db->fetchArray('SELECT pkgID FROM BlockTypes WHERE btHandle = ?', [$btHandle])) {
            $pkgID = (int) $bt[0];
            if ($pkgID > 0) {
                $pkgHandle = PackageList::getHandle($pkgID);
            }
        }

        return $pkgHandle;
    }

    /**
     * @param $btHandle
     * @param $pkgHandle
     *
     * @return bool
     */
    public static function refreshBlockType($btHandle, $pkgHandle)
    {
        // If it's an installed block type, we can retrieve it this way and refresh it
        if ($bt = BlockType::getByHandle($btHandle)) {
            $bt->refresh();

            return true;
        }

        // Otherwise, partial refresh code below taken from the core of concrete5, minor changes made to refresh
        $em = ORM::entityManager();
        $em->flush();
        self::clearOverrideCache();
        $env = Environment::get();
        $r = $env->getRecord(DIRNAME_BLOCKS . '/' . $btHandle . '/' . FILENAME_BLOCK_DB, $pkgHandle);
        if ($r->exists()) {
            $app = Facade::getFacadeApplication();
            $db = $app->make('database')->connection();
            $parser = Schema::getSchemaParser(simplexml_load_file($r->file));
            $parser->setIgnoreExistingTables(false);
            $toSchema = $parser->parse($db);
            $fromSchema = $db->getSchemaManager()->createSchema();
            $schemaDiff = (new Comparator())->compare($fromSchema, $toSchema);
            $saveQueries = $schemaDiff->toSaveSql($db->getDatabasePlatform());
            foreach ($saveQueries as $query) {
                $db->query($query);
            }
        }

        return true;
    }
}
