<?php

namespace ccxtpro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import

class okcoin extends okex3 {

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'id' => 'okcoin',
            'name' => 'OKCoin',
            'countries' => array( 'CN', 'US' ),
            'hostname' => 'okcoin.com',
            'pro' => true,
            'urls' => array(
                'api' => array(
                    'ws' => 'wss://real.okcoin.com:8443/ws/v3',
                ),
                'logo' => 'https://user-images.githubusercontent.com/1294454/27766791-89ffb502-5ee5-11e7-8a5b-c5950b68ac65.jpg',
                'www' => 'https://www.okcoin.com',
                'doc' => 'https://www.okcoin.com/docs/en/',
                'fees' => 'https://www.okcoin.com/coin-fees',
                'referral' => 'https://www.okcoin.com/account/register?flag=activity&channelId=600001513',
            ),
            'fees' => array(
                'trading' => array(
                    'taker' => 0.002,
                    'maker' => 0.001,
                ),
            ),
            'options' => array(
                'fetchMarkets' => array( 'spot' ),
            ),
        ));
    }
}
