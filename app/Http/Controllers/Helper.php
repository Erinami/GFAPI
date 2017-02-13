<?php
/**
 * Created by PhpStorm.
 * User: Erina
 * Date: 12/11/2016
 * Time: 5:32 PM
 */

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Girl;

// helper class for the rest api controllers
class Helper
{

    private static $girlColumnNames = [
        'girl_id', 'girl_name_official_eng', 'girl_name_romanization_eng', 'girl_name', 'girl_image_name', 'girl_age', 'girl_authority',
        'girl_birthday_date', 'girl_birthday', 'girl_blood', 'girl_bust', 'girl_class_name', 'girl_club', 'girl_club_eng', 'girl_cv', 'girl_cv_eng',
        'girl_description', 'girl_description_eng', 'girl_favorite_food', 'girl_favorite_food_eng', 'girl_favorite_subject', 'girl_favorite_subject_eng',
        'girl_attribute', 'girl_hated_food', 'girl_hated_food_eng', 'girl_height', 'girl_hip', 'girl_hobby', 'girl_horoscope', 'girl_horoscope_eng', 'girl_name_hiragana',
        'girl_nickname', 'girl_nickname_eng', 'girl_school', 'girl_school_eng', 'girl_tweet_name', 'girl_waist', 'girl_weight', 'girl_year'
    ];
    private static $girlColumnTypes = [
        'int', 'string', 'string', 'string', 'string', 'int', 'string',
        'date', 'date', 'string', 'int', 'string', 'string', 'string', 'string', 'string',
        'string', 'string', 'string', 'string', 'string', 'string',
        'string', 'string', 'string', 'int', 'int', 'string', 'string', 'string', 'string',
        'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int'
    ];

    // column names and types of the cards table
    private static $cardColumnNames = [
        "card_id", "card_set_position", "card_stat_display", "card_image_name", "card_attribute", "card_cost", "card_description", "card_description_eng",
        "card_disposal", "card_before_evolution_uid", "card_evolution_uid", "card_max_level", "card_initial_level", "card_skill_name",
        "card_skill_name_eng", "card_name", "card_name_eng", "card_rarity", "card_strongest_level", "card_skill_description", "card_skill_description_eng",
        "card_initial_attack_base", "card_initial_defense_base", "card_max_attack_base", "card_max_defense_base", "card_macarons", "card_type", "card_ringable"
    ];
    private static $cardColumnTypes = [
        "int", "int", "int", "string", "string", "int", "string", "string",
        "int", "int", "int", "int", "int", "string",
        "string", "string", "string", "int", "int", "string", "string",
        "int", "int", "int", "int", "int", "string", "int"
    ];

    // column names and types of the sets table
    private static $setColumnNames = [
        "set_id", "set_type", "set_name_initial", "set_name_initial_eng", "set_name_final", "set_name_final_eng", "set_size", "set_rarity"
    ];
    private static $setColumnTypes = [
        "int", "string", "string", "string", "string", "string", "int", "int"
    ];

    // based on the string passed in, return the correct operator
    public static function getOperator($op) {
        if (strcasecmp('lt', $op) == 0) {
            return '<';
        } else if (strcasecmp('gt', $op) == 0) {
            return '>';
        } else if (strcasecmp('eq', $op) == 0) {
            return '=';
        } else if (strcasecmp('ne', $op) == 0) {
            return '!=';
        } else if (strcasecmp('lk', $op) == 0) {
            return 'LIKE';
        } else if (strcasecmp('li', $op) == 0) {
            return 'xx'; // NOTE THAT THIS DOES NOT WORK WITH MYSQL
        } else if (strcasecmp('le', $op) == 0) {
            return '<=';
        } else if (strcasecmp('ge', $op) == 0) {
            return '>=';
        } else {
            return 'xx';
        }
    }

    public static function getColumns() {
        // column names and types that we're going to loop through
        $columnNames = array_merge(self::$girlColumnNames, self::$cardColumnNames, self::$setColumnNames);
        $columnTypes = array_merge(self::$girlColumnTypes, self::$cardColumnTypes, self::$setColumnTypes);
        $columns = array_combine($columnNames, $columnTypes);
        return $columns;
    }

    public static function checkColumn($columnName) {
        if (in_array($columnName, self::$cardColumnNames)) {
            $table = "cards";
        } else if (in_array($columnName, self::$girlColumnNames)) {
            $table = "girls";
        } else if (in_array($columnName, self::$setColumnNames)) {
            $table = "sets";
        } else {
            return "error 500";
        }
        return $table;
    }

    public static function rarityToInteger($rarity) {

        // if its a valid number, just return the number
        if (is_numeric($rarity)) {
            if (intval($rarity) <= 7 && intval($rarity) >= 1) {
                return $rarity;
            } else {
                return "invalid";
            }
        }

        $rarity = strtoupper($rarity);
        if (strcmp($rarity, 'N') == 0) {
            return 0;
        } else if (strcmp($rarity, 'HN') == 0) {
            return 1;
        } else if (strcmp($rarity, 'R') == 0) {
            return 2;
        } else if (strcmp($rarity, 'HR') == 0) {
            return 3;
        } else if (strcmp($rarity, 'SR') == 0) {
            return 4;
        } else if (strcmp($rarity, 'SSR') == 0) {
            return 5;
        } else if (strcmp($rarity, 'UR') == 0) {
            return 6;
        } else {
            return "invalid";
        }
    }
    
    public static function processRequestParameters($or_tiers, $columns, $input, $joins, $tieredArray) {
        // -----------------------------------------LOOP THROUGH REQUEST PARAMETERS------------------------------------

        $parameters = array_keys($input);
        $parameters_size = sizeof($parameters);

        // loop through all request input parameters
        for ($i = 0; $i < $parameters_size; $i++) {
            $parameter = $parameters[$i];

            // if the column name exists in the request params, then we process the query, else we ignore it
            if (array_key_exists($parameter, $columns)) {
                $value = $input[$parameter];
                $columnName = $parameter;
                $columnType = $columns[$parameter];
                // trim white space and remove curly brackets, then separate based on the colon

                // ---------------------------PRE-PROCESSING (TRIM and SPLIT)-----------------------------------------

                $temp = trim($value);
                $temp = str_replace('{', '', $temp);
                $temp = str_replace('}', '', $temp);

                // triple colon separates multiple criteria in one column name. this allows for searching in between
                // values - for example, age={lt::18:::gt::15} would mean searching for an age between 15 and 18
                // non-inclusive.
                $temp = explode(':::', $temp);
                $temp_size = count($temp);
                for ($j = 0; $j < $temp_size; $j++) {
                    // separates the criteria by a double colon - if separated into two parts, the first should be
                    // the operator, and the second is the value.
                    $parts = explode('::', $temp[$j]);
                    $numparts = sizeof($parts);

                    // initializes variables (not necessary tbh)
                    $op = '=';
                    $val = '';
                    $tier = 1;

                    // ------------------------------------PARSE THE VALUE--------------------------------------------

                    // check how many parts are in the value
                    // to specify an or-tier, you MUST specify the operator.
                    // the format is assumed as OPERATOR, VALUE, OR-TIER
                    if ($numparts == 3) { // if three parts, then an operator, value, and or-tier was specified.
                        $op = $parts[0];
                        $op = trim($op);
                        $op = Helper::getOperator($op);
                        if (strcasecmp($op, 'xx') == 0) {
                            return response(["error" => "Invalid operator specified. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                        }
                        $val = $parts[1];
                        $val = trim($val);
                        if (!is_numeric($parts[2])) {
                            return response(["error" => "Invalid or tier specified. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                        }
                        $tier = intval($parts[2]);

                    } else if ($numparts == 2) { // two parts means that they supplied both an operator and a query, but no or-tier
                        $op = $parts[0];
                        $op = trim($op);
                        $op = Helper::getOperator($op);
                        if (strcasecmp($op, 'xx') == 0) {
                            return response(["error" => "Invalid operator specified. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                        }
                        $val = $parts[1];
                        $val = trim($val);

                    } else if ($numparts == 1) { // one part means that they only supplied a value. assume '=' for the operator.
                        $op = '=';
                        $val = $parts[0];
                        $val = trim($val);

                    } else { // otherwise, it's invalid
                        return response(["error" => "Invalid number of arguments in a parameter. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                    }

                    // -----------------------------------RARITY CONVERSION---------------------------------------------

                    // check if card rarity and set rarity was given - if the user supplied the character versions, we need to
                    // convert them into integers
                    if (strcmp('card_rarity', $columnName) == 0) {
                        $card_rarity = Helper::rarityToInteger($val);
                        if (strcmp($card_rarity, 'invalid') == 0) {
                            return response(["error" => "Card rarity is invalid. Please enter either the shorthand characters or the numerical equivalent."], 400);
                        } else {
                            $val = $card_rarity;
                        }
                    }

                    if (strcmp('set_rarity', $columnName) == 0) {
                        $set_rarity = Helper::rarityToInteger($val);
                        if (strcmp($set_rarity, 'invalid') == 0) {
                            return response(["error" => "Set rarity is invalid. Please enter either the shorthand characters or the numerical equivalent."], 400);
                        } else {
                            $val = $set_rarity;
                        }
                    }

                    // -------------------------------------------TYPE CHECKING---------------------------------------

                    // now, do type checking and casting
                    if (strcmp($columnType, 'int') == 0) {
                        if (is_numeric($val)) {
                            $val = intval($val);
                        } else {
                            return response(["error" => "Expected integer, but got a different invalid type. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                        }
                    } else if (strcmp($columnType, 'string') == 0) {
                        $val = strval($val);
                    } else if (strcmp($columnType, 'date')) {
                        $val = strtotime($val);
                    }

                    // ----------------------------------ADD PERCENT SIGN TO LIKE-------------------------------------

                    // surround value with percent signs if its a like
                    if (strcmp($op, 'LIKE') == 0) {
                        $val = '%' . $val . '%';
                    }

                    // ----------------------------------CHECK PARENT TABLE, AND JOINS--------------------------------

                    // check which table the column belongs to, so we can append the right table name as its prefix
                    $table = Helper::checkColumn($columnName);

                    // if for some reason the column doesn't belong to any of the tables, something really bad
                    // must have happened.
                    if (strcmp($table, "error 500") == 0) {
                        return response(["error" => "Something went wrong. Seems like the offender was " . $value . " in " . $columnName . "."], 500);
                    }
                    $arrayColumn = $table . '.' . $columnName;

                    // also check to see if we need to add any joins to the query
                    $joins[$table] = 1;

                    // ----------------------------------CHECK FOR BANNED TERMS---------------------------------------

                    // check for any of these banned terms. probably completely unnecessary, but better safe than sorry
                    if (stripos(strval($val), 'select') !== false || stripos(strval($val), 'update') !== false || stripos(strval($val), 'insert') !== false
                        || stripos(strval($val), 'drop') !== false || stripos(strval($val), 'delete') !== false || stripos(strval($val), 'alter') !== false
                        || stripos(strval($val), 'join') !== false
                    ) {
                        return response(["error" => "Something went wrong. Please contact an administrator."], 500);
                    }

                    // ----------------------------------CHECK OR-TIER CONSISTENCY------------------------------------

                    if ($tier > $or_tiers) {
                        return response(["error" => "Or-Tier out of range specified. Seems like the offender was " . $value . " in " . $columnName . "."], 400);
                    }

                    // ------------------------------------------PUSH-------------------------------------------------

                    // add it to our list of where criteria
                    array_push($tieredArray[$tier - 1], [$arrayColumn, $op, $val]);
                }
            }
        }
        
        $returnArray = [$tieredArray, $joins];
        return response($returnArray, 200);

    }
}
