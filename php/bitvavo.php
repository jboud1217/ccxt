<?php

namespace ccxtpro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \ccxt\AuthenticationError;
use \ccxt\ArgumentsRequired;

class bitvavo extends \ccxt\bitvavo {

    use ClientTrait;

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'has' => array(
                'ws' => true,
                'watchOrderBook' => true,
                'watchTrades' => true,
                'watchTicker' => true,
                'watchOHLCV' => true,
                'watchOrders' => true,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => 'wss://ws.bitvavo.com/v2',
                ),
            ),
            'options' => array(
                'tradesLimit' => 1000,
                'ordersLimit' => 1000,
                'OHLCVLimit' => 1000,
            ),
        ));
    }

    public function watch_public($name, $symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market($symbol);
        $messageHash = $name . '@' . $market['id'];
        $url = $this->urls['api']['ws'];
        $request = array(
            'action' => 'subscribe',
            'channels' => [
                array(
                    'name' => $name,
                    'markets' => [
                        $market['id'],
                    ],
                ),
            ],
        );
        $message = array_merge($request, $params);
        return $this->watch($url, $messageHash, $message, $messageHash);
    }

    public function watch_ticker($symbol, $params = array ()) {
        return $this->watch_public('ticker24h', $symbol, $params);
    }

    public function handle_ticker($client, $message) {
        //
        //     {
        //         $event => 'ticker24h',
        //         $data => array(
        //             {
        //                 $market => 'ETH-EUR',
        //                 open => '193.5',
        //                 high => '202.72',
        //                 low => '192.46',
        //                 last => '199.01',
        //                 volume => '3587.05020246',
        //                 volumeQuote => '708030.17',
        //                 bid => '199.56',
        //                 bidSize => '4.14730803',
        //                 ask => '199.57',
        //                 askSize => '6.13642074',
        //                 timestamp => 1590770885217
        //             }
        //         )
        //     }
        //
        $event = $this->safe_string($message, 'event');
        $tickers = $this->safe_value($message, 'data', array());
        for ($i = 0; $i < count($tickers); $i++) {
            $data = $tickers[$i];
            $marketId = $this->safe_string($data, 'market');
            if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
                $messageHash = $event . '@' . $marketId;
                $market = $this->markets_by_id[$marketId];
                $ticker = $this->parse_ticker($data, $market);
                $symbol = $ticker['symbol'];
                $this->tickers[$symbol] = $ticker;
                $client->resolve ($ticker, $messageHash);
            }
        }
        return $message;
    }

    public function watch_trades($symbol, $since = null, $limit = null, $params = array ()) {
        $future = $this->watch_public('trades', $symbol, $params);
        return $this->after($future, array($this, 'filter_by_since_limit'), $since, $limit, 'timestamp', true);
    }

    public function handle_trade($client, $message) {
        //
        //     {
        //         event => 'trade',
        //         timestamp => 1590779594547,
        //         $market => 'ETH-EUR',
        //         id => '450c3298-f082-4461-9e2c-a0262cc7cc2e',
        //         amount => '0.05026233',
        //         price => '198.46',
        //         side => 'buy'
        //     }
        //
        $marketId = $this->safe_string($message, 'market');
        $market = null;
        $symbol = $marketId;
        if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
            $market = $this->markets_by_id[$marketId];
            $symbol = $market['symbol'];
        }
        $name = 'trades';
        $messageHash = $name . '@' . $marketId;
        $trade = $this->parse_trade($message, $market);
        $array = $this->safe_value($this->trades, $symbol);
        if ($array === null) {
            $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
            $array = new ArrayCache ($limit);
        }
        $array->append ($trade);
        $this->trades[$symbol] = $array;
        $client->resolve ($array, $messageHash);
    }

    public function watch_ohlcv($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market($symbol);
        $name = 'candles';
        $marketId = $market['id'];
        $interval = $this->timeframes[$timeframe];
        $messageHash = $name . '@' . $marketId . '_' . $interval;
        $url = $this->urls['api']['ws'];
        $request = array(
            'action' => 'subscribe',
            'channels' => array(
                array(
                    'name' => 'candles',
                    'interval' => array( $interval ),
                    'markets' => array( $marketId ),
                ),
            ),
        );
        $message = array_merge($request, $params);
        $future = $this->watch($url, $messageHash, $message, $messageHash);
        return $this->after($future, array($this, 'filter_by_since_limit'), $since, $limit, 0, true);
    }

    public function find_timeframe($timeframe) {
        // redo to use reverse lookups in a static map instead
        $keys = is_array($this->timeframes) ? array_keys($this->timeframes) : array();
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            if ($this->timeframes[$key] === $timeframe) {
                return $key;
            }
        }
        return null;
    }

    public function handle_ohlcv($client, $message) {
        //
        //     {
        //         event => 'candle',
        //         $market => 'BTC-EUR',
        //         $interval => '1m',
        //         $candle => array(
        //             array(
        //                 1590797160000,
        //                 '8480.9',
        //                 '8480.9',
        //                 '8480.9',
        //                 '8480.9',
        //                 '0.01038628'
        //             )
        //         )
        //     }
        //
        $name = 'candles';
        $marketId = $this->safe_string($message, 'market');
        $symbol = null;
        $market = null;
        if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
            $market = $this->markets_by_id[$marketId];
            $symbol = $market['symbol'];
        }
        $interval = $this->safe_string($message, 'interval');
        // use a reverse lookup in a static map instead
        $timeframe = $this->find_timeframe($interval);
        $messageHash = $name . '@' . $marketId . '_' . $interval;
        $candles = $this->safe_value($message, 'candle');
        $this->ohlcvs[$symbol] = $this->safe_value($this->ohlcvs, $symbol, array());
        $stored = $this->safe_value($this->ohlcvs[$symbol], $timeframe, array());
        for ($i = 0; $i < count($candles); $i++) {
            $candle = $candles[$i];
            $parsed = $this->parse_ohlcv($candle, $market);
            $length = is_array($stored) ? count($stored) : 0;
            if ($length && ($parsed[0] === $stored[$length - 1][0])) {
                $stored[$length - 1] = $parsed;
            } else {
                $stored[] = $parsed;
                $limit = $this->safe_integer($this->options, 'OHLCVLimit', 1000);
                if ($length >= $limit) {
                    array_shift($stored);
                }
            }
        }
        $this->ohlcvs[$symbol][$timeframe] = $stored;
        $client->resolve ($stored, $messageHash);
    }

    public function watch_order_book($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market($symbol);
        $name = 'book';
        $messageHash = $name . '@' . $market['id'];
        $url = $this->urls['api']['ws'];
        $request = array(
            'action' => 'subscribe',
            'channels' => [
                array(
                    'name' => $name,
                    'markets' => [
                        $market['id'],
                    ],
                ),
            ],
        );
        $subscription = array(
            'messageHash' => $messageHash,
            'name' => $name,
            'symbol' => $symbol,
            'marketId' => $market['id'],
            'method' => array($this, 'handle_order_book_subscription'),
            'limit' => $limit,
            'params' => $params,
        );
        $message = array_merge($request, $params);
        $future = $this->watch($url, $messageHash, $message, $messageHash, $subscription);
        return $this->after($future, array($this, 'limit_order_book'), $symbol, $limit, $params);
    }

    public function handle_delta($bookside, $delta) {
        $price = $this->safe_float($delta, 0);
        $amount = $this->safe_float($delta, 1);
        $bookside->store ($price, $amount);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function handle_order_book_message($client, $message, $orderbook) {
        //
        //     {
        //         event => 'book',
        //         market => 'BTC-EUR',
        //         $nonce => 36947383,
        //         bids => array(
        //             array( '8477.8', '0' )
        //         ),
        //         asks => array(
        //             array( '8550.9', '0' )
        //         )
        //     }
        //
        $nonce = $this->safe_integer($message, 'nonce');
        if ($nonce > $orderbook['nonce']) {
            $this->handle_deltas($orderbook['asks'], $this->safe_value($message, 'asks', array()));
            $this->handle_deltas($orderbook['bids'], $this->safe_value($message, 'bids', array()));
            $orderbook['nonce'] = $nonce;
        }
        return $orderbook;
    }

    public function handle_order_book($client, $message) {
        //
        //     {
        //         $event => 'book',
        //         $market => 'BTC-EUR',
        //         nonce => 36729561,
        //         bids => array(
        //             array( '8513.3', '0' ),
        //             array( '8518.8', '0.64236203' ),
        //             array( '8513.6', '0.32435481' ),
        //         ),
        //         asks => array()
        //     }
        //
        $event = $this->safe_string($message, 'event');
        $marketId = $this->safe_string($message, 'market');
        $market = null;
        $symbol = null;
        if ($marketId !== null) {
            if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
                $market = $this->markets_by_id[$marketId];
                $symbol = $market['symbol'];
            }
        }
        $messageHash = $event . '@' . $market['id'];
        $orderbook = $this->safe_value($this->orderbooks, $symbol);
        if ($orderbook === null) {
            return;
        }
        if ($orderbook['nonce'] === null) {
            $subscription = $this->safe_value($client->subscriptions, $messageHash, array());
            $watchingOrderBookSnapshot = $this->safe_value($subscription, 'watchingOrderBookSnapshot');
            if ($watchingOrderBookSnapshot === null) {
                $subscription['watchingOrderBookSnapshot'] = true;
                $client->subscriptions[$messageHash] = $subscription;
                $options = $this->safe_value($this->options, 'watchOrderBookSnapshot', array());
                $delay = $this->safe_integer($options, 'delay', $this->rateLimit);
                // fetch the snapshot in a separate async call after a warmup $delay
                $this->delay($delay, array($this, 'watch_order_book_snapshot'), $client, $message, $subscription);
            }
            $orderbook->cache[] = $message;
        } else {
            $this->handle_order_book_message($client, $message, $orderbook);
            $client->resolve ($orderbook, $messageHash);
        }
    }

    public function watch_order_book_snapshot($client, $message, $subscription) {
        $symbol = $this->safe_string($subscription, 'symbol');
        $limit = $this->safe_integer($subscription, 'limit');
        $params = $this->safe_value($subscription, 'params');
        $marketId = $this->safe_string($subscription, 'marketId');
        $name = 'getBook';
        $messageHash = $name . '@' . $marketId;
        $url = $this->urls['api']['ws'];
        $request = array(
            'action' => $name,
            'market' => $marketId,
        );
        $future = $this->watch($url, $messageHash, $request, $messageHash, $subscription);
        return $this->after($future, array($this, 'limit_order_book'), $symbol, $limit, $params);
    }

    public function handle_order_book_snapshot($client, $message) {
        //
        //     {
        //         action => 'getBook',
        //         $response => {
        //             $market => 'BTC-EUR',
        //             nonce => 36946120,
        //             bids => array(
        //                 array( '8494.9', '0.24399521' ),
        //                 array( '8494.8', '0.34884085' ),
        //                 array( '8493.9', '0.14535128' ),
        //             ),
        //             asks => array(
        //                 array( '8495', '0.46982463' ),
        //                 array( '8495.1', '0.12178267' ),
        //                 array( '8496.2', '0.21924143' ),
        //             )
        //         }
        //     }
        //
        $response = $this->safe_value($message, 'response');
        if ($response === null) {
            return $message;
        }
        $marketId = $this->safe_string($response, 'market');
        $symbol = null;
        if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
            $market = $this->markets_by_id[$marketId];
            $symbol = $market['symbol'];
        }
        $name = 'book';
        $messageHash = $name . '@' . $marketId;
        $orderbook = $this->orderbooks[$symbol];
        $snapshot = $this->parse_order_book($response);
        $snapshot['nonce'] = $this->safe_integer($response, 'nonce');
        $orderbook->reset ($snapshot);
        // unroll the accumulated deltas
        $messages = $orderbook->cache;
        for ($i = 0; $i < count($messages); $i++) {
            $message = $messages[$i];
            $this->handle_order_book_message($client, $message, $orderbook);
        }
        $this->orderbooks[$symbol] = $orderbook;
        $client->resolve ($orderbook, $messageHash);
    }

    public function handle_order_book_subscription($client, $message, $subscription) {
        $symbol = $this->safe_string($subscription, 'symbol');
        $limit = $this->safe_integer($subscription, 'limit');
        if (is_array($this->orderbooks) && array_key_exists($symbol, $this->orderbooks)) {
            unset($this->orderbooks[$symbol]);
        }
        $this->orderbooks[$symbol] = $this->order_book(array(), $limit);
    }

    public function handle_order_book_subscriptions($client, $message, $marketIds) {
        $name = 'book';
        for ($i = 0; $i < count($marketIds); $i++) {
            $marketId = $this->safe_string($marketIds, $i);
            if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
                $market = $this->markets_by_id[$marketId];
                $symbol = $market['symbol'];
                $messageHash = $name . '@' . $marketId;
                if (!(is_array($this->orderbooks) && array_key_exists($symbol, $this->orderbooks))) {
                    $subscription = $this->safe_value($client->subscriptions, $messageHash);
                    $method = $this->safe_value($subscription, 'method');
                    if ($method !== null) {
                        $method($client, $message, $subscription);
                    }
                }
            }
        }
    }

    public function watch_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' watchOrders requires a $symbol argument');
        }
        $this->load_markets();
        $authenticate = $this->authenticate();
        $market = $this->market($symbol);
        $marketId = $market['id'];
        $url = $this->urls['api']['ws'];
        $name = 'account';
        $subscriptionHash = $name . '@' . $marketId;
        $messageHash = $subscriptionHash . '_' . 'order';
        $request = array(
            'action' => 'subscribe',
            'channels' => array(
                array(
                    'name' => $name,
                    'markets' => array( $marketId ),
                ),
            ),
        );
        $future = $this->after_dropped($authenticate, array($this, 'watch'), $url, $messageHash, $request, $subscriptionHash);
        return $this->after($future, array($this, 'filter_by_symbol_since_limit'), $symbol, $since, $limit);
    }

    public function handle_order($client, $message) {
        //
        //     {
        //         $event => 'order',
        //         $orderId => 'f0e5180f-9497-4d05-9dc2-7056e8a2de9b',
        //         $market => 'ETH-EUR',
        //         created => 1590948500319,
        //         updated => 1590948500319,
        //         status => 'new',
        //         side => 'sell',
        //         orderType => 'limit',
        //         amount => '0.1',
        //         amountRemaining => '0.1',
        //         price => '300',
        //         onHold => '0.1',
        //         onHoldCurrency => 'ETH',
        //         selfTradePrevention => 'decrementAndCancel',
        //         visible => true,
        //         timeInForce => 'GTC',
        //         postOnly => false
        //     }
        //
        $name = 'account';
        $event = $this->safe_string($message, 'event');
        $marketId = $this->safe_string($message, 'market');
        $messageHash = $name . '@' . $marketId . '_' . $event;
        $symbol = $marketId;
        $market = null;
        if (is_array($this->markets_by_id) && array_key_exists($marketId, $this->markets_by_id)) {
            $market = $this->markets_by_id[$marketId];
            $symbol = $market['symbol'];
        }
        $order = $this->parse_order($message, $market);
        $orderId = $order['id'];
        $defaultKey = $this->safe_value($this->orders, $symbol, array());
        $defaultKey[$orderId] = $order;
        $this->orders[$symbol] = $defaultKey;
        $result = array();
        $values = is_array($this->orders) ? array_values($this->orders) : array();
        for ($i = 0; $i < count($values); $i++) {
            $orders = is_array($values[$i]) ? array_values($values[$i]) : array();
            $result = $this->array_concat($result, $orders);
        }
        // delete older $orders from our structure to prevent memory leaks
        $limit = $this->safe_integer($this->options, 'ordersLimit', 1000);
        $result = $this->sort_by($result, 'timestamp');
        $resultLength = is_array($result) ? count($result) : 0;
        if ($resultLength > $limit) {
            $toDelete = $resultLength - $limit;
            for ($i = 0; $i < $toDelete; $i++) {
                $id = $result[$i]['id'];
                $symbol = $result[$i]['symbol'];
                unset($this->orders[$symbol][$id]);
            }
            $result = mb_substr($result, $toDelete, $resultLength - $toDelete);
        }
        $client->resolve ($result, $messageHash);
    }

    public function handle_subscription_status($client, $message) {
        //
        //     {
        //         event => 'subscribed',
        //         $subscriptions => {
        //             book => array( 'BTC-EUR' )
        //         }
        //     }
        //
        $subscriptions = $this->safe_value($message, 'subscriptions', array());
        $methods = array(
            'book' => array($this, 'handle_order_book_subscriptions'),
        );
        $names = is_array($subscriptions) ? array_keys($subscriptions) : array();
        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $method = $this->safe_value($methods, $name);
            if ($method !== null) {
                $subscription = $this->safe_value($subscriptions, $name);
                $method($client, $message, $subscription);
            }
        }
        return $message;
    }

    public function authenticate() {
        $url = $this->urls['api']['ws'];
        $client = $this->client($url);
        $future = $client->future ('authenticated');
        $action = 'authenticate';
        $authenticated = $this->safe_value($client->subscriptions, $action);
        if ($authenticated === null) {
            try {
                $this->check_required_credentials();
                $timestamp = $this->milliseconds();
                $stringTimestamp = (string) $timestamp;
                $auth = $stringTimestamp . 'GET/' . $this->version . '/websocket';
                $signature = $this->hmac($this->encode($auth), $this->encode($this->secret));
                $request = array(
                    'action' => $action,
                    'key' => $this->apiKey,
                    'signature' => $signature,
                    'timestamp' => $timestamp,
                );
                $this->spawn(array($this, 'watch'), $url, $action, $request, $action);
            } catch (Exception $e) {
                $client->reject ($e, 'authenticated');
                // allows further authentication attempts
                if (is_array($client->subscriptions) && array_key_exists($action, $client->subscriptions)) {
                    unset($client->subscriptions[$action]);
                }
            }
        }
        return $future;
    }

    public function handle_authentication_message($client, $message) {
        //
        //     {
        //         $event => 'authenticate',
        //         $authenticated => true
        //     }
        //
        $authenticated = $this->safe_value($message, 'authenticated', false);
        if ($authenticated) {
            // we resolve the $future here permanently so authentication only happens once
            $future = $this->safe_value($client->futures, 'authenticated');
            $future->resolve (true);
        } else {
            $error = new AuthenticationError ($this->json($message));
            $client->reject ($error, 'authenticated');
            // allows further authentication attempts
            $event = $this->safe_value($message, 'event');
            if (is_array($client->subscriptions) && array_key_exists($event, $client->subscriptions)) {
                unset($client->subscriptions[$event]);
            }
        }
    }

    public function sign_message($client, $messageHash, $message, $params = array ()) {
        // todo => implement signMessage
        return $message;
    }

    public function handle_message($client, $message) {
        //
        //     {
        //         $event => 'subscribed',
        //         subscriptions => {
        //             book => array( 'BTC-EUR' )
        //         }
        //     }
        //
        //
        //     {
        //         $event => 'book',
        //         market => 'BTC-EUR',
        //         nonce => 36729561,
        //         bids => array(
        //             array( '8513.3', '0' ),
        //             array( '8518.8', '0.64236203' ),
        //             array( '8513.6', '0.32435481' ),
        //         ),
        //         asks => array()
        //     }
        //
        //     {
        //         $action => 'getBook',
        //         response => {
        //             market => 'BTC-EUR',
        //             nonce => 36946120,
        //             bids => array(
        //                 array( '8494.9', '0.24399521' ),
        //                 array( '8494.8', '0.34884085' ),
        //                 array( '8493.9', '0.14535128' ),
        //             ),
        //             asks => array(
        //                 array( '8495', '0.46982463' ),
        //                 array( '8495.1', '0.12178267' ),
        //                 array( '8496.2', '0.21924143' ),
        //             )
        //         }
        //     }
        //
        //     {
        //         $event => 'authenticate',
        //         authenticated => true
        //     }
        //
        $methods = array(
            'subscribed' => array($this, 'handle_subscription_status'),
            'book' => array($this, 'handle_order_book'),
            'getBook' => array($this, 'handle_order_book_snapshot'),
            'trade' => array($this, 'handle_trade'),
            'candle' => array($this, 'handle_ohlcv'),
            'ticker24h' => array($this, 'handle_ticker'),
            'authenticate' => array($this, 'handle_authentication_message'),
            'order' => array($this, 'handle_order'),
        );
        $event = $this->safe_string($message, 'event');
        $method = $this->safe_value($methods, $event);
        if ($method === null) {
            $action = $this->safe_string($message, 'action');
            $method = $this->safe_value($methods, $action);
            if ($method === null) {
                return $message;
            } else {
                return $method($client, $message);
            }
        } else {
            return $method($client, $message);
        }
    }
}
