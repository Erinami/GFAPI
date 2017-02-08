<?php

namespace App\Http\Controllers;

use DB;
use Image;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Card;

/**
 * Created by PhpStorm.
 * User: Erina
 * Date: 2/2/2017
 * Time: 5:00 PM
 */

/**
 * Class RandomController
 *
 * Simulates random rolls of cards
 * Will contain a fully rarity-probability customizable function, as well as a few presets for popular card rolls
 * (Normal gacha roll, Normal Platinum gacha roll, HR+ SR10%, HR+ SR30%, HR+ SR50%)
 *
 * @package App\Http\Controllers
 */
class RandomController extends Controller
{
    /**
     * Returns a random card.
     *
     * User must specify probability of each rarity occurring, or not specify any probabilities to return
     * each card with a equal probability (~1/7500). Rarity needs to be specified in the request parameters as
     * N_Probability, HN_Probability, and so on. The sum of the probabilities must equal 1.
     *
     * The card number of the returned card is returned as a header (Card_Number) and can be used/decoded
     * in other programs if required.
     *
     * @param Request $request the request sent to the server
     * @return mixed
     */
    public static function getRandomCard(Request $request) {
        $randomCard = null;

        // get the request parameters
        $input = $request->all();

        // check if request parameters contains any probability values. if so, we check these
        // when returning the random card. any unspecified probability values are assumed to be zero.
        if (array_key_exists('N_Probability', $input) || array_key_exists('HN_Probability', $input)
            || array_key_exists('R_Probability', $input)
            || array_key_exists('HR_Probability', $input)
            || array_key_exists('SR_Probability', $input)
            || array_key_exists('SSR_Probability', $input)
            || array_key_exists('UR_Probability', $input)) {

            // make sure the user did not include a card_rarity constraint, because that would invalidate the point
            // of the rarity probability system
            if (array_key_exists('card_rarity', $input) || array_key_exists('set_rarity', $input)) {
                return response(["error" => "Cannot specify a card rarity constraint when choosing random probabilities!"], 400);
            }

            // array initialization
            $listOfCards = [null, null, null, null, null, null, null]; // will store returned lists of cards by CardController method
            $rarities = ['N', 'HN', 'R', 'HR', 'SR', 'SSR', 'UR']; // probability names for use in card_rarity field
            $raritiesTemp = ['N_Probability', 'HN_Probability', 'R_Probability', // specified rarities
                'HR_Probability', 'SR_Probability', 'SSR_Probability',
                'UR_Probability'];
            $raritiesCumulative = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]; // cumulative rarities used to compare based on random number
            $total = 0.0; // total so far (same as $raritiesCumulative[count($raritiesCumulative) - 1])

            // go through all the user-specified rarities and make sure they are valid and numeric floats
            for ($i = 0; $i < count($raritiesTemp); $i++) {
                if (array_key_exists($raritiesTemp[$i], $input)) {
                    $rarityValue = $input[$raritiesTemp[$i]];
                    if (is_numeric($rarityValue)) {
                        $temp = floatval($rarityValue);
                        $total = $total + $temp;
                    } else {
                        return response(["error" => "Invalid probabilities given. Make sure the probability value is numeric and between 0 to 1 (as a decimal)."], 400);
                    }
                    $raritiesCumulative[$i] = $total;
                }
            }

            // make sure probabilities sum up to 1
            if ($total != 1) {
                return response(["error" => "Invalid probabilities given. The sum of all probabilities must equal 1.00."], 400);
            }

            // inner loop to go through all rarities and get cards from them
            for ($i = 0; $i < count($rarities); $i++) {
                // add the rarity constraint
                $request->merge(['card_rarity' => $rarities[$i]]);
                $response = CardController::getCollection($request, 0);

                // make sure the response given was OK (200-299 inclusive)
                if ($response->status() < 200 || $response->status() > 299) {
                    return response(["error" => "Invalid response was returned. The error was: " . $response->getOriginalContent()], 500);
                }

                // get the cards, and make sure its not empty
                $tempCards = $response->getOriginalContent();
                $listOfCards[$i] = $tempCards;
            }

            // generate random number between 0 and 1 inclusive, then determine what rarity that means we have chosen
            $cards = null;
            $random = 0.0 + mt_rand() / mt_getrandmax();
            if ($random >= 0 && $random < $raritiesCumulative[0] && 0 != $raritiesCumulative[0]) {
                $cards = $listOfCards[0];
            } else if ($random >= $raritiesCumulative[0] && $random < $raritiesCumulative[1] && $raritiesCumulative[0] != $raritiesCumulative[1]) {
                $cards = $listOfCards[1];
            } else if ($random >= $raritiesCumulative[1] && $random < $raritiesCumulative[2] && $raritiesCumulative[1] != $raritiesCumulative[2]) {
                $cards = $listOfCards[2];
            } else if ($random >= $raritiesCumulative[2] && $random < $raritiesCumulative[3] && $raritiesCumulative[2] != $raritiesCumulative[3]) {
                $cards = $listOfCards[3];
            } else if ($random >= $raritiesCumulative[3] && $random < $raritiesCumulative[4] && $raritiesCumulative[3] != $raritiesCumulative[4]) {
                $cards = $listOfCards[4];
            } else if ($random >= $raritiesCumulative[4] && $random < $raritiesCumulative[5] && $raritiesCumulative[4] != $raritiesCumulative[5]) {
                $cards = $listOfCards[5];
            } else if ($random >= $raritiesCumulative[5] && $random <= $raritiesCumulative[6] && $raritiesCumulative[5] != $raritiesCumulative[6]) {
                $cards = $listOfCards[6];
            } else {
                return response(["error" => "An error occurred while processing probabilities."], 400);
            }

            // make sure that the chosen cards list is not empty
            if (count($cards) < 1) {
                return response(["error" => "No cards were returned. Try different constraints."], 400);
            }
            $randomCard = $cards[mt_rand(0, count($cards) - 1)];

        // if no rarity probabilities were specified, just return all cards based on the user constraints
        } else if (!array_key_exists('N_Probability', $input) && !array_key_exists('HN_Probability', $input)
            && !array_key_exists('R_Probability', $input)
            && !array_key_exists('HR_Probability', $input)
            && !array_key_exists('SR_Probability', $input)
            && !array_key_exists('SSR_Probability', $input)
            && !array_key_exists('UR_Probability', $input)) {

            $response = CardController::getCollection($request, 0);

            // make sure the response given was OK (200-299 inclusive)
            if ($response->status() < 200 || $response->status() > 299) {
                return response(["error" => "Invalid response was returned. The error was: " . $response->getOriginalContent()], 500);
            }

            // get the cards, and make sure its not empty
            $cards = $response->getOriginalContent();

            if (count($cards) < 1) {
                return response(["error" => "No cards were returned. Try different constraints."], 400);
            }
            $randomCard = $cards[mt_rand(0, count($cards) - 1)];

        } else {
            return response(["error" => "Invalid probabilities given. Either include no probabilities, or probabilities of all rarities from N to UR."], 400);
        }

        // return the random card
        $imageName = $randomCard->card_image_name;
        $strURL = "/images/cards/full/" . (string)$imageName . ".jpg";
        if (file_exists(public_path() . $strURL)) {
            $input = $request->all();
            if (array_key_exists('size', $input)) {
                if (!is_numeric($input['size'])) {
                    return response(["error" => "Size must be a numerical value."], 400);
                }
                $size = intval($input['size']);
                return Image::make(public_path() . $strURL)->resize($size, null, function($constraint){$constraint->aspectRatio();})->response('png')->header("Card_Number", $randomCard->card_id);
            }
            return Image::make(public_path() . $strURL)->response('png')->header("Card_Number", $randomCard->card_id);
        }
        return response(["error" => "An error occurred."], 500);
    }

}