<?php

declare(strict_types=1);

namespace LemonbrainServices\C5Backup;

defined('C5_EXECUTE') or die('Access Denied.');

class C5SqlDumpProcess
{
    public const FILE_TYPE_SQL = '.sql';

    /**
     * Dump DB into target directory
     */
    public function start($targetPath = null)
    {
        $dateTime = new \DateTime();
        $date = $dateTime->format('YmdHi');
        if (getenv('CONCRETE5_ENV') === 'exigo') {
            $fileName = getenv('exigo_db_name') . '_' . $date . self::FILE_TYPE_SQL;
            $username = getenv('exigo_db_username');
            $database = getenv('exigo_db_name');
            $password = getenv('exigo_db_password');
            $host = getenv('exigo_db_server');
        } else {
            $fileName = getenv('DB_DATABASE') . '_' . $date . self::FILE_TYPE_SQL;
            $username = getenv('DB_USERNAME');
            $database = getenv('DB_DATABASE');
            $password = getenv('DB_PASSWORD');
            $host = getenv('DB_HOSTNAME');
        }

        /**
         * For reference see https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html
         */
        $flagsForV8 = getenv('MYSQL_VERSION') !== '8.0' ? '' : '--column-statistics=0 --no-tablespaces';

        $mySqlDumpCommand = "mysqldump {$flagsForV8} -h {$host} -u {$username} -p'{$password}' {$database} > {$targetPath}/{$fileName}";

        @exec($mySqlDumpCommand);
    }
}
