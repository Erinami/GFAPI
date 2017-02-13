<?php

namespace App\Http\Controllers;

use DB;
use Image;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Girl;

/**
 * Created by PhpStorm.
 * User: Erina
 * Date: 2/2/2017
 * Time: 5:00 PM
 */

/**
 * Class BirthdayController
 *
 * Remaining TODOs:
 *      -Have all birthday functions take in a date parameter (they, by default, process today's date, but can take
 *          any different date as input as well
 *
 * @package App\Http\Controllers
 */
class BirthdayController extends Controller
{
    /**
     * Show the girls who have a birthday on this date today.
     *
     * Uses the girl_birthday_date column, where every girl's birthday year is replaced by 2015 for easy searching.
     *
     */
    public function getBirthdaysToday()
    {
        // set timezone
        date_default_timezone_set('Asia/Tokyo');

        // hardcode year to 2015 to match the years stored in the db, so we can do pure date comparisons
        $date = date('2015-m-d');

        // find girl(s) who has the same birthday date (month and day)
        $girls = Girl::whereDate('girl_birthday_date', '=', $date)->get();
        return $girls;
    }

    /**
     * Check if there are any girls with a birthday today. (to simplify wiki logic)
     */
    public function getBirthdaysTodayExist() {
        date_default_timezone_set('Asia/Tokyo');
        $date = date('2015-m-d');
        $girls = Girl::whereDate('girl_birthday_date', '=', $date)->get();

        // check the count of girls to see if anybody has a birthday on this day
        if (count($girls) < 1) {
            return response()->json(['exists' => "false"]);
        } else {
            return response()->json(['exists' => "true"]);
        }
    }

    /**
     * Gets a nice wiki message to place into the template
     */
    public function getBirthdaysWikiMessage() {
        date_default_timezone_set('Asia/Tokyo');
        $date = date('2015-m-d');
        $girls = Girl::whereDate('girl_birthday_date', '=', $date)->get();
        $count = 1;

        // handle message editing for the wiki - need to include [[]] around the girl name for hyperlinking
        $message = "It's ";
        foreach ($girls as $girl) {
            if ($count != count($girls)) {
                $message = $message . "[[" . $girl->girl_name_official_eng . "]] and ";
            } else {
                $message = $message . "[[" . $girl->girl_name_official_eng . "]]'s birthday today!";
            }
            $count = $count + 1;
        }
        if (count($girls) == 0) {
            $message = "Nobody has a birthday today.";
        }
        return response()->json(['message' => $message]);
    }

    /**
     * Returns a picture of the birthday girl.
     * Note that this function will return the default theater picture of the girl.
     *
     * @param Request $request the request sent to the server
     * @return mixed
     */
    public function getBirthdaysTodayPicture(Request $request) {
        date_default_timezone_set('Asia/Tokyo');
        $date = date('2015-m-d');
        $girls = Girl::whereDate('girl_birthday_date', '=', $date)->get();
        $girl = null;
        if (count($girls) > 1) {
            $index = rand(0, count($girls) - 1);
            $girl = $girls[$index];
        } else if (count($girls) == 1) {
            $girl = $girls[0];
        } else {
            return null;
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

    /**
     * Returns a picture of the birthday girl.
     * Note that this function will return a random card of the girl.
     *
     * @param Request $request the request sent to the server
     * @return mixed
     */
    public function getBirthdaysTodayCard(Request $request) {
        date_default_timezone_set('Asia/Tokyo');
        $date = date('2015-m-d');
        $girls = Girl::whereDate('girl_birthday_date', '=', $date)->get();
        $girl = null;

        // if we have more than one girl having a birthday today, choose a random one to display a card from
        if (count($girls) > 1) {
            $index = mt_rand(0, count($girls) - 1);
            $girl = $girls[$index];
        } else if (count($girls) == 1) {
            $girl = $girls[0];
        } else {
            return null;
        }

        // see if user specified a rarity constraint, if so, process it
        $rarity = null;
        $input = $request->all();
        if (array_key_exists('card_rarity', $input)) {
            $rarity = $input['card_rarity'];
        }

        // create essentially a new request parameter for passing into another function
        $request->replace(['girl_id' => $girl->girl_id]);
        if ($rarity) {
            $request->merge(['card_rarity' => $rarity]);
        }

        // we already made a robust search, so just search with girl_id and card_rarity parameters if specified
        $response = CardController::getCollection($request, 0);

        // make sure the response given was OK (200-299 inclusive)
        if ($response->status() < 200 || $response->status() > 299) {
            return response(["error" => "Invalid response was returned. The error was: " . $response->getOriginalContent()], 500);
        }

        // get the cards, and make sure its not empty
        $cards = $response->getOriginalContent();
        if (count($cards) < 1) {
            return response(["error" => "No cards were returned. Try different rarity constraints.", 400]);
        }

        // pick a random card, then display the card image
        $randomCard = $cards[mt_rand(0, count($cards) - 1)];
        $imageName = $randomCard->card_image_name;
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

}