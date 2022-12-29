<?php
/**
 * models/user.inc
 *
 * An example of using the Ooro class.
 * Manage the user table.
 *
 * table schema(sample) :
 *      CREATE TABLE `user` (
 *       `user_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *       `nickname` varchar(15) NOT NULL DEFAULT '',
 *       `login_id` varchar(30) NOT NULL DEFAULT '',
 *       `login_passwd` varchar(50) NOT NULL DEFAULT '',
 *       `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
 *       `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
 *       PRIMARY KEY (`user_no`)
 *      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
 */

namespace shakeFlat\models;
use shakeFlat\Ooro;

class mUser extends Ooro
{
    protected function __construct($userNo)
    {
        parent::__construct([
            "connection_name"   => "default",
            "table_name"        => "user",
            "pk_field"          => array ( "user_no" ),
            "pk_value"          => array ( $userNo ),
            "exclude_fields"    => array ( "create_date", "modify_date" ),  // A field that is always set to the value set in default
            "preload_data"      => null,
            "nodata_error"      => false,
        ]);
    }
}