<?php

namespace App\Http\Controllers;

use DB;
use Image;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Girl;


/**
 * Welcome back! Here's what you need to do now:
 *
 * 1. Duplicate GirlController for CardController and SetController, and make sure they work properly as well.
 * 2. Work on the BirthdayController so that the Birthdays section of the wiki can work again.
 * 3. Work on the RandomController for RNG-simulation in Discord.
 * 4. Add advanced RNG features to Discord bot, as well as simple searching.
 * 5. If you managed to finish all that before returning from Japan, work on script crawler for valid stat info
 * 6. After returning from Japan:
 *      Finish writing Discord bot to finish authentication/role management/admin management
 *      Populate DB to add the newest cards
 *      Write a script that crawls valid stat information for cards from the girl pages, and update the stats in the DB
 *      Open Discord group to public <-- THIS IS THE END GOAL!
 *
 * Don't forget to WORK ON THE DAMN WIKI TOO, and look into rankings.gfkari.com.
 *
 * A few important design considerations to remember:
 *
 *      -Although the values for image_name in cards and sets is being duplicated, this value should not ever change.
 *          Simplifying this will make future work a lot easier.
 *      -Same thing applies for set_size, which can be calculated on the fly. However, doing so would make the robust
 *          search very difficult to accomplish. Since ameba cannot add more cards to a set after release, it is
 *          almost guaranteed that the set_size will never change after a card has entered the game.
 *      -We currently cannot use < or > on rarity measures as intended, meaning a simple search for "cards of
 *          rarity less than HR" will not work - it will do a string comparison instead. The search can still be
 *          done by specifying a lot of or_tiers, so the process will proceed without changing the rarities
 *          to numerical values for now. However - this needs to be changed soon.
 */

/**
 * Class GirlController
 *
 * Remaining TODOs:
 *      Look at CardController - same TODOs apply for this class.
 *
 * @package App\Http\Controllers
 */
class GirlController extends Controller
{
    // returns data for a single girl via id
    // this data is not enclosed within an array
    public function getInformation($id)
    {
        $girl = Girl::find($id);
        if ($girl != null) {
            return response($girl, 200);
        }
        return response(["error" => ErrorMessages::$invalidId], ErrorMessages::$invalidIdCode);
    }

    // returns data for a list of girls through the request parameters
    // will silently fail for invalid numerical ids
    public function getMultipleInformation(Request $request)
    {
        // the number of items we want to display per page
        $items_per_page = 50;
        // check if request overwrote this value
        $input = $request->all();
        if (array_key_exists('items_per_page', $input)) {
            if (!is_numeric($input['items_per_page'])) {
                return response(["error" => "Items per page must be a numerical value."], 400);
            }
            $items_per_page = intval($input['items_per_page']);
        }

        // check to make sure that the user gave us ids
        if (!array_key_exists('girl_id', $input)) {
            return response(["error" => "No ids were given."], 400);
        }

        $temp = $input['girl_id']; // get ids from request
        $temp = trim($temp); // trim white space
        $girl_ids = explode(',', $temp); // separate the ids
        $length = count($girl_ids); // count how many

        // start the query off
        $query = DB::table('girls');

        // keep adding the ids as OR statements to the where clause
        for ($i = 0; $i < $length; $i++) {
            $girl_ids[$i] = trim($girl_ids[$i]);
            if (is_numeric($girl_ids[$i])) {
                $val = intval($girl_ids[$i]);
            } else {
                return response(["error" => "Invalid ID was given: " . $girl_ids[$i] . "."], 400);
            }
            if ($i == 0) {
                $query = $query->where("girl_id", '=', $val);
            } else {
                $query = $query->orWhere("girl_id", '=', $val);
            }
        }
        $results = $query->paginate($items_per_page);
        return response($results, 200);
    }

    // returns the picture for the girl given the id
    // takes in a size parameter of the width of the image, which does dynamic resizing
    public function getPicture($id, Request $request)
    {
        // find the girl using eloquent, and make sure id is valid
        $girl = Girl::find($id);
        if ($girl == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $imageName = $girl->girl_image_name;
        $strURL = "/images/girls/" . (string)$imageName . ".png";
        if (file_exists(public_path() . $strURL)) {
            $input = $request->all();
            if (array_key_exists('size', $input)) {
                if (!is_numeric($input['size'])) {
                    return response(["error" => "Size must be a numerical value."], 400);
                }
                $size = intval($input['size']);
                return Image::make(public_path() . $strURL)->resize($size, null, function($constraint){$constraint->aspectRatio();})->response('png');
            }
            return Image::make(public_path() . $strURL)->response('png');
        }
        return response(["error" => "An error occurred."], 500);
    }

    /** Get all cards associated with a girl
     * @param $id the girl id to get cards of
     * @param Request $request the request sent to the server
     * @return Response the list of cards that the girl has
     */
    public function getCards($id, Request $request)
    {
        // the number of items we want to display per page
        $items_per_page = 50;
        // check if request overwrote this value
        $input = $request->all();
        if (array_key_exists('items_per_page', $input)) {
            if (!is_numeric($input['items_per_page'])) {
                return response(["error" => "Items per page must be a numerical value."], 400);
            }
            $items_per_page = intval($input['items_per_page']);
        }

        // use ELOQUENT, and check to make sure girl id is valid
        $girl = Girl::find($id);
        if ($girl == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $results = $girl->cards;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageSearchResults = $results->slice(($currentPage - 1) * $items_per_page, $items_per_page)->all();
        $paginatedResults = new LengthAwarePaginator($currentPageSearchResults, count($results), $items_per_page);
        $paginatedResults->setPath($request->url());
        $paginatedResults->appends($request->except(['page']));

        return response($paginatedResults, 200);
    }

    /** Get all sets associated with a girl
     * @param $id the girl id to get cards of
     * @param Request $request the request sent to the server
     * @return Response the list of cards that the girl has
     */
    public function getSets($id, Request $request)
    {
        // the number of items we want to display per page
        $items_per_page = 50;
        // check if request overwrote this value
        $input = $request->all();
        if (array_key_exists('items_per_page', $input)) {
            if (!is_numeric($input['items_per_page'])) {
                return response(["error" => "Items per page must be a numerical value."], 400);
            }
            $items_per_page = intval($input['items_per_page']);
        }

        // check to make sure girl id is valid
        $girl = Girl::find($id);
        if ($girl == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }

        // eloquent does not yet support belongsToManyThrough.
        $results = DB::table('girls')
            ->join('girls_cards', 'girls.girl_id', '=', 'girls_cards.girl_id')
            ->join('cards', 'girls_cards.card_id', '=', 'cards.card_id')
            ->join('sets', 'cards.set_id', '=', 'sets.set_id')
            ->where('girls.girl_id', '=', $id)
            ->groupBy('sets.set_id')
            ->select('sets.set_id', 'sets.set_type', 'sets.set_name_initial', 'sets.set_name_initial_eng', 'sets.set_image_name_initial', 'sets.set_image_name_final', 'sets.set_size')
            ->distinct()->get();

        // this was the original plan - it works, but it grew too ugly, and will become even uglier during set searching.
        // so, the image name columns were added to the sets. it's more redundant, but it will save work in the long run.
        // see the comments at the end of this file for more details.

//        $results = DB::select("SELECT DISTINCT(sets.set_id), q.card_image_name AS 'set_image_name_initial', z.card_image_name AS 'set_image_name_final' FROM girls
//            INNER JOIN girls_cards ON girls.girl_id = girls_cards.girl_id
//            INNER JOIN cards ON girls_cards.card_id = cards.card_id INNER JOIN sets ON cards.set_id = sets.set_id
//            INNER JOIN
//            (SELECT cards.card_image_name, t.set_id FROM
//            (SELECT sets.set_id, MAX(card_id) AS 'max', MIN(card_id) as 'min' FROM `sets`
//            INNER JOIN cards ON cards.set_id = sets.set_id
//            GROUP BY sets.set_id) t
//            INNER JOIN cards ON t.min = cards.card_id WHERE cards.set_id = t.set_id) q
//            ON sets.set_id = q.set_id
//            INNER JOIN
//            (SELECT cards.card_image_name, x.set_id FROM
//            (SELECT sets.set_id, MAX(card_id) AS 'max', MIN(card_id) as 'min' FROM `sets`
//            INNER JOIN cards ON cards.set_id = sets.set_id
//            GROUP BY sets.set_id) x
//            INNER JOIN cards ON x.max = cards.card_id WHERE cards.set_id = x.set_id) z
//            ON sets.set_id = z.set_id");

        // manual pagination with the LengthAwarePaginator class since laravel fails to paginate when distinct is used
//        $collection = Collection::make($results);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageSearchResults = $results->slice(($currentPage - 1) * $items_per_page, $items_per_page)->all();
        $paginatedResults = new LengthAwarePaginator($currentPageSearchResults, count($results), $items_per_page);
        $paginatedResults->setPath($request->url());
        $paginatedResults->appends($request->except(['page']));

        return response($paginatedResults, 200);
    }

    /**
     * Returns a collection of girls based on the search criteria specified.
     * Specify each column that you want to filter on in the request parameters as the key.
     * The value should follow the format of {OPERATOR::VALUE}, where operator is the type of operation you want to
     * filter with (=, <, <=, >, etc. as short names that can be found in Helper.php), and value is the value that
     * you want the operator to use. Separate the operator and value by a double colon.
     *
     * If you want to specify multiple conditions in one column, use a triple colon to separate them. For example,
     * age={lt::18:::gt::15} would mean searching for an age between 15 and 18.
     *
     * You can provide any column that the Girl table is joined with for searching - so Cards and Sets table are fair
     * game. In the future, the relationships table and songs will be added as join criteria as well.
     * 
     * It's robust, so it only joins when relationships are involved. It unfortunately joins on one-to-many
     * relationships as well, which may be fixed in the future depending on how large and complicated the joins
     * get, but I'm not expecting that to happen - mostly simple queries will probably happen.
     * 
     * @param Request $request the request sent to the server
     * @param int $paginated whether or not to return results as paginated or not
     * @return Response|LengthAwarePaginator the returned results based on the request parameters
     */
    public function getCollection(Request $request, $paginated = 1)
    {
        // -------------------------------------------------INITIALIZATION---------------------------------------------

        // creates the where array for the where clause
        $tieredArray = [];

        // get columns
        $columns = Helper::getColumns();

        // parse the request params
        $input = $request->all();

        // determine what joins are necessary (initialize all to not necessary)
        $joins = ['girls' => 1, 'cards' => 0, 'sets' => 0];

        // ------------------------------------------CHECK FOR PAGINATION PARAMETER------------------------------------

        // the number of items we want to display per page
        $items_per_page = 50;
        // check if request overwrote this value
        if (array_key_exists('items_per_page', $input)) {
            if (!is_numeric($input['items_per_page'])) {
                return response(["error" => "Items per page must be a numerical value."], 400);
            }
            $items_per_page = intval($input['items_per_page']);
        }

        // -------------------------------------------------CHECK OR-TIERS---------------------------------------------

        $or_tiers = 1;
        // check if user specified multiple OR tiers
        if (array_key_exists('or_tiers', $input)) {
            if (!is_numeric($input['or_tiers'])) {
                return response(["error" => "Or tiers must be a numerical value."], 400);
            }
            $or_tiers = intval($input['or_tiers']);
            if ($or_tiers < 1) { // or tiers must be at least 1 - you have to have at least one tier of search criteria!
                return response(["error" => "Invalid number of or tiers."], 400);
            }
        }

        // add the tiers to the while array
        for ($i = 0; $i < $or_tiers; $i++) {
            $tieredArray[$i] = [];
        }

        // -------------------------------------------------CHECK SORT CRITERIA----------------------------------------

        // defaults to sorting by girl_id, if invalid name is provided, it will fail
        $sort_column = 'girls.girl_id';
        if (array_key_exists('sort_column', $input)) {
            $temp_sort_column = $input['sort_column'];
            if (array_key_exists($temp_sort_column, $columns)) {
                if (strcmp(Helper::checkColumn($temp_sort_column), 'girls') == 0) {
                    $sort_column = Helper::checkColumn($temp_sort_column) . "." . $temp_sort_column;
                } else {
                    return response(["error" => "Invalid sorting column given."], 400);
                }
            }
        }

        $sort_order = 'asc';
        if (array_key_exists('sort_order', $input)) {
            $temp_sort_order = $input['sort_order'];
            if (strcmp('desc', $temp_sort_order) == 0) {
                $sort_order = 'desc';
            } else if (strcmp('asc', $temp_sort_order) != 0) {
                return response(["error" => "Invalid sorting order given."], 400);
            }
        }

        // -------------------------------------------PROCESS PARAMETERS----------------------------------------------

        // calls a helper method to process the request parameters / search terms given by the user
        $helperResponse = Helper::processRequestParameters($or_tiers, $columns, $input, $joins, $tieredArray);

        // check if helper method returned an error response
        if ($helperResponse->status() > 299 || $helperResponse->status() < 200) {
            return $helperResponse;
        }

        // if no errors, get the tieredArray (processed parameters in the form of where clauses) and joins array (tells what tables we need to join with)
        $helperReturnedArrays = $helperResponse->getOriginalContent();
        $tieredArray = $helperReturnedArrays[0];
        $joins = $helperReturnedArrays[1];
        // ----------------------------------------------------QUERY--------------------------------------------------

        // queries the database with the stuff we specified
        $query = DB::table('girls');

        // check for the joins that need to be done
        // cards-sets-megasets grouping
        if ($joins['sets'] == 1) { // if a set needs to be checked, then cards+sets need to be joined
            $query = $query->join('girls_cards', 'girls.girl_id', '=', 'girls_cards.girl_id')
                ->join('cards', 'girls_cards.card_id', '=', 'cards.card_id')
                ->join('sets', 'cards.set_id', '=', 'sets.set_id');
        } else if ($joins['cards'] == 1) { // otherwise, if just card needs to be checked, only join with cards and not sets
            $query = $query->join('girls_cards', 'girls.girl_id', '=', 'girls_cards.girl_id')
                ->join('cards', 'girls_cards.card_id', '=', 'cards.card_id');
        }

        for ($i = 0; $i < $or_tiers; $i++) {
            if ($i == 0) {
                $query = $query->where($tieredArray[$i]);
            } else {
                $query = $query->orWhere($tieredArray[$i]);
            }
        }

        $results = $query->select('girls.girl_id', 'girl_name_official_eng', 'girl_name_romanization_eng', 'girl_name', 'girl_image_name', 'girl_age', 'girl_authority',
            'girl_birthday_date', 'girl_birthday', 'girl_blood', 'girl_bust', 'girl_class_name', 'girl_club', 'girl_club_eng', 'girl_cv', 'girl_cv_eng',
            'girl_description', 'girl_description_eng', 'girl_favorite_food', 'girl_favorite_food_eng', 'girl_favorite_subject', 'girl_favorite_subject_eng',
            'girl_attribute', 'girl_hated_food', 'girl_hated_food_eng', 'girl_height', 'girl_hip', 'girl_hobby', 'girl_horoscope', 'girl_horoscope_eng', 'girl_name_hiragana',
            'girl_nickname', 'girl_nickname_eng', 'girl_school', 'girl_school_eng', 'girl_tweet_name', 'girl_waist', 'girl_weight', 'girl_year')
            ->orderBy($sort_column, $sort_order)->distinct()->get();

        // --------------------------------------------------PAGINATION-----------------------------------------------

        // check if pagination is necessary (default yes), if not we just return the results
        if ($paginated == 0) {
            return response($results, 200);
        }

        // manual pagination with the LengthAwarePaginator class since laravel fails to paginate when distinct is used
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageSearchResults = $results->slice(($currentPage - 1) * $items_per_page, $items_per_page)->all();
        $paginatedResults = new LengthAwarePaginator($currentPageSearchResults, count($results), $items_per_page);
        $paginatedResults->setPath($request->url());
        $paginatedResults->appends($request->except(['page']));

        return response($paginatedResults, 200);

    }
}



/**
 * The following SQL statement is to update/create the image_name columns in the sets table.
 * Although there is redundancy, this value should never change after being set, so it should
 * save time/work in the long run.

UPDATE sets SET sets.set_image_name_initial=
(SELECT cards.card_image_name FROM
(SELECT sets.set_id, MAX(card_id) AS 'max', MIN(card_id) as 'min' FROM `sets`
INNER JOIN cards ON cards.set_id = sets.set_id
GROUP BY sets.set_id) t
INNER JOIN cards ON t.min = cards.card_id WHERE sets.set_id = t.set_id);

 *
 * Also: use below format to update size (yes, it is now a hard-coded column to simplify things)

UPDATE table1 A
INNER JOIN (SELECT id,COUNT(*) idcount FROM table2 GROUP BY id) as B
ON B.id = A.id
SET A.Freq = B.idcount
 *
 * Below adds rarities to the sets table (can replace max to min or vice versa)
 *
UPDATE sets A
INNER JOIN (SELECT C.set_id, cards.card_rarity FROM
(SELECT sets.set_id, MAX(card_id) AS 'max' FROM sets INNER JOIN cards ON cards.set_id = sets.set_id GROUP BY sets.set_id) C
INNER JOIN cards ON C.max = cards.card_id) B
ON A.set_id = B.set_id
SET A.set_rarity = B.card_rarity

 */