<?php
namespace ccxt;
use \ccxt\Precise;
use React\Async;
use React\Promise;

// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

// -----------------------------------------------------------------------------
include_once __DIR__ . '/../base/test_borrow_rate.php';

function test_fetch_borrow_rate($exchange, $code) {
    return Async\async(function () use ($exchange, $code) {
        $method = 'fetchBorrowRate';
        $borrow_rate = null;
        try {
            $borrow_rate = Async\await($exchange->fetch_borrow_rate($code));
        } catch(Exception $ex) {
            $message = ((string) $ex);
            // for exchanges, atm, we don't have the correct lists of currencies, which currency is borrowable and which not. So, because of our predetermined list of test-currencies, some of them might not be borrowable, and thus throws exception. However, we shouldn't break tests for that specific exceptions, and skip those occasions.
            if (array_search('could not find the borrow rate for currency code', $message) < 0) {
                throw new Error($message);
            }
            // console.log (method + '() : ' + code + ' is not borrowable for this exchange. Skipping the test method.');
            return;
        }
        test_borrow_rate($exchange, $method, $borrow_rate, $code);
    }) ();
}
