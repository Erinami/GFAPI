<?php

namespace App\Http\Controllers;

use DB;
use Image;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Card;

/**
 * Class CardController
 *
 * Class should be easily duplicated for the other entities (cards and sets) as well.
 * 
 * Remaining TODOs:
 *      -Add more methods as necessary when actually building the GUI.
 *
 *      -Search is fairly robust at this point, and will likely only take small changes for the foreseeable future.
 *      -Result of collection searching returns a lot of data due to the sheer amount of columns.
 *          -One way to fix this is to allow the user to choose what columns they want, but this means sending a new
 *              request every time a column is added - too many unnecessary requests/refreshing!
 *          -Could also have a lightweight search option that just uses less columns, and default to that
 *
 *      -Look into more error handling and edge case checking - a lot of these are still unaccounted for
 *
 * Luckily, these TODOs can probably wait until the other parts are in motion. We need to have something to show first!
 *
 * @package App\Http\Controllers
 */
class CardController extends Controller
{
    // returns data for a single card via id
    // this data is not enclosed within an array
    public function getInformation($id)
    {
        $card = Card::find($id);
        if ($card != null) {
            return response($card, 200);
        }
        return response(["error" => "Invalid ID was given."], 400);
    }

    // returns data for a list of cards through the request parameters
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
        if (!array_key_exists('card_id', $input)) {
            return response(["error" => "No ids were given."], 400);
        }

        $temp = $input['card_id']; // get ids from request
        $temp = trim($temp); // trim white space
        $card_ids = explode(',', $temp); // separate the ids
        $length = count($card_ids); // count how many

        // start the query off
        $query = DB::table('cards');

        // keep adding the ids as OR statements to the where clause
        for ($i = 0; $i < $length; $i++) {
            $card_ids[$i] = trim($card_ids[$i]);
            if (is_numeric($card_ids[$i])) {
                $val = intval($card_ids[$i]);
            } else {
                return response(["error" => "Invalid ID was given: " . $card_ids[$i] . "."], 400);
            }
            if ($i == 0) {
                $query = $query->where("card_id", '=', $val);
            } else {
                $query = $query->orWhere("card_id", '=', $val);
            }
        }
        $results = $query->paginate($items_per_page);
        return response($results, 200);
    }

    // returns the picture for the card given the id
    // takes in a size parameter of the width of the image, which does dynamic resizing
    public function getPicture($id, Request $request)
    {
        $card = Card::find($id);
        if ($card == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $imageName = $card->card_image_name;
        $strURL = "/images/cards/full/" . (string)$imageName . ".jpg";
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

    // returns the icon for the card given the id
    // takes in a size parameter of the width of the image, which does dynamic resizing
    public function getIcon($id, Request $request)
    {
        $card = Card::find($id);
        if ($card == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $imageName = $card->card_image_name;
        $strURL = "/images/cards/icon/" . (string)$imageName . ".jpg";
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

    /** Get all girls associated with a card
     * @param $id the card id to get girls of
     * @param Request $request the request sent to the server
     * @return Response the list of girls that the card has
     */
    public function getGirls($id, Request $request)
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

        // use ELOQUENT, also check if card ID is valid
        $card = Card::find($id);
        if ($card == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $results = $card->girls;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageSearchResults = $results->slice(($currentPage - 1) * $items_per_page, $items_per_page)->all();
        $paginatedResults = new LengthAwarePaginator($currentPageSearchResults, count($results), $items_per_page);
        $paginatedResults->setPath($request->url());
        $paginatedResults->appends($request->except(['page']));

        return response($paginatedResults, 200);
    }

    /** Gets the set associated with the card
     * This data is not enclosed within an array
     * @param $id the card id to get the set from
     * @param Request $request the request sent to the server
     * @return Response the set that the card belongs to
     */
    public function getSet($id, Request $request)
    {
        // get request parameters (doesn't seem like this function will use any though)
        $input = $request->all();

        // use ELOQUENT, also check if card id is valid
        $card = Card::find($id);
        if ($card == null) {
            return response(["error" => "Invalid ID was given."], 400);
        }
        $results = $card->set;
        return response($results, 200);
    }

    /**
     * Returns a collection of cards based on the search criteria specified.
     * Specify each column that you want to filter on in the request parameters as the key.
     * The value should follow the format of {OPERATOR::VALUE}, where operator is the type of operation you want to
     * filter with (=, <, <=, >, etc. as short names that can be found in Helper.php), and value is the value that
     * you want the operator to use. Separate the operator and value by a double colon.
     *
     * If you want to specify multiple conditions in one column, use a triple colon to separate them. For example,
     * age={lt::18:::gt::15} would mean searching for an age between 15 and 18.
     *
     * You can provide any column that the Card table is joined with for searching - so Girls and Sets table are fair
     * game. In the future, the relationships table and songs will be added as join criteria as well.
     *
     * You can also sort by any visible column using sort_column, and choose asc or desc by specifying sort_order
     * in the request parameters.
     *
     * It's robust, so it only joins when relationships are involved. It unfortunately joins on one-to-many
     * relationships as well, which may be fixed in the future depending on how large and complicated the joins
     * get, but I'm not expecting that to happen - mostly simple queries will probably happen.
     *
     * @param Request $request the request sent to the server
     * @param int $paginated whether or not to return results as paginated or not
     * @return Response|LengthAwarePaginator the returned results based on the request parameters
     */
    public static function getCollection(Request $request, $paginated = 1)
    {
        // -------------------------------------------------INITIALIZATION---------------------------------------------

        // creates the where array for the where clause
        $tieredArray = [];

        // get columns
        $columns = Helper::getColumns();

        // parse the request params
        $input = $request->all();
        $parameters = array_keys($input);
        $parameters_size = sizeof($parameters);

        // determine what joins are necessary (initialize all to not necessary)
        $joins = ['girls' => 0, 'cards' => 1, 'sets' => 0];

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

        // defaults to card_id, will FAIL SILENTLY if an invalid column name is provided. This may need to be fixed.
        $sort_column = 'cards.card_id';
        if (array_key_exists('sort_column', $input)) {
            $temp_sort_column = $input['sort_column'];
            if (array_key_exists($temp_sort_column, $columns)) {
                if (strcmp(Helper::checkColumn($temp_sort_column), 'cards') == 0) {
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
        $query = DB::table('cards');

        // check for the joins that need to be done

        // sets check
        if ($joins['sets'] == 1) { // if a set needs to be checked, then join with sets
            $query = $query->join('sets', 'cards.set_id', '=', 'sets.set_id');
        }
        
        // girls check (we don't need to join with sets to join with girls, so these two are independent of each other
        if ($joins['girls'] == 1) {
            $query = $query->join('girls_cards', 'cards.card_id', '=', 'girls_cards.card_id')
                ->join('girls', 'girls_cards.girl_id', '=', 'girls.girl_id');
        }

        for ($i = 0; $i < $or_tiers; $i++) {
            if ($i == 0) {
                $query = $query->where($tieredArray[$i]);
            } else {
                $query = $query->orWhere($tieredArray[$i]);
            }
        }

        $results = $query->select("cards.card_id", "card_set_position", "card_stat_display", "card_image_name", "card_attribute", "card_cost", "card_description", "card_description_eng",
            "card_disposal", "card_before_evolution_uid", "card_evolution_uid", "card_max_level", "card_initial_level", "card_skill_name",
            "card_skill_name_eng", "card_name", "card_name_eng", "card_rarity", "card_strongest_level", "card_skill_description", "card_skill_description_eng",
            "card_initial_attack_base", "card_initial_defense_base", "card_max_attack_base", "card_max_defense_base", "card_macarons", "card_type", "card_ringable")
            ->orderBy($sort_column, $sort_order)
            ->distinct()->get();

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
