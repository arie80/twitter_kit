<?php
App::import('Core', 'Xml');
App::import('vendor', 'TwitterKit.HttpSocketOauth', array('file' => 'http_socket_oauth' . DS .'http_socket_oauth.php'));
/**
 * Twitter API Datasouce
 *
 * for CakePHP 1.3.0
 * PHP version 5.2 upper
 *
 * Copyright 2010, elasticconsultants.com
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010 elasticconsultants co.,ltd.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.models.datasouces
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 *
 * @see http://apiwiki.twitter.com/Twitter-API-Documentation
 *
 * This Class use HttpSocketOAuth:
 *
 *   Neil Crookes » OAuth extension to CakePHP HttpSocket
 *     http://www.neilcrookes.com/2010/04/12/cakephp-oauth-extension-to-httpsocket/
 *     http://github.com/neilcrookes/http_socket_oauth
 *
 * Thank you.
 */
class TwitterSource extends DataSource {

    public $description = 'Twitter API';

    /**
     *
     * @var HttpSocketOauth
     */
    public $Http;

    /**
     *
     * @var string
     */
    public $oauth_consumer_key;

    /**
     *
     * @var string
     */
    public $oauth_consumer_secret;

    /**
     *
     * @var string
     */
    public $oauth_callback;

    /**
     *
     * @var string
     */
    public $oauth_token;

    /**
     *
     * @var string
     */
    public $oauth_token_secret;

    const HTTP_URL = 'http://twitter.com/';

    const HTTPS_URL = 'https://twitter.com/';

    const TWITTER_API_URL_BASE = 'http://api.twitter.com/';

    /**
     *
     * @var array
     */
    public $_baseConfig = array(
        'oauth_consumer_key' => '',
        'oauth_consumer_secret' => '',
        'oauth_token' => '',
        'oauth_token_secret' => '',
        'oauth_callback' => '',
    );

    /**
     *
     * @param array $config
     */
    public function __construct($config) {

        parent::__construct($config);
         
        $this->Http =& new HttpSocketOauth();
         
        $this->reset();
    }

    /**
     * Reset object vars
     */
    public function reset() {

        $this->oauth_consumer_key    = $this->config['oauth_consumer_key'];
        $this->oauth_consumer_secret = $this->config['oauth_consumer_secret'];
        $this->oauth_token           = $this->config['oauth_token'];
        $this->oauth_token_secret    = $this->config['oauth_token_secret'];
        $this->oauth_callback        = $this->config['oauth_callback'];

    }

    /**
     * set OAuth Token
     *
     * @param mixed  $token
     * @param string $secret
     * @return ture|false
     */
    public function setToken($token, $secret = null) {

        if (is_array($token) && !empty($token['oauth_token']) && !empty($token['oauth_token_secret'])) {

            $this->oauth_token        = $token['oauth_token'];
            $this->oauth_token_secret = $token['oauth_token_secret'];

            return true;

        } else if (!empty($token) && !empty($secret)) {

            $this->oauth_token        = $token;
            $this->oauth_token_secret = $secret;

            return true;
        }

        return false;
    }

    /**
     * Request API and process responce
     *
     * @param array $params
     * @param bool  $is_process
     * @return mixed
     */
    protected function _request($params, $is_process = true) {

        $response = $this->Http->request($params);

        if ($is_process) {

            $response = json_decode($response, true);

        }

        return $response;
    }

    /**
     * Build request array
     *
     * @param string $url
     * @param string $method
     * @param array  $body   GET: query string POST: post data
     * @return array
     */
    protected function _buildRequest($url, $method = 'GET', $body = array()) {

        $method = strtoupper($method);

        // extract path
        if (!preg_match('!^http!', $url)) {

            $url = self::TWITTER_API_URL_BASE . $url;

        }

        $uri = parse_url($url);

        // add GET params
        if (!empty($body) && $method == 'GET') {

            $uri['query'] = array_merge($uri['query'], $body);
            $body = array();

        }

        $params = compact('uri', 'method', 'body');

        // -- Set Auth parameter
        if (!empty($this->oauth_consumer_key) && !empty($this->oauth_consumer_secret)) {

            // OAuth
            $params['auth']['method'] = 'OAuth';
            $params['auth']['oauth_consumer_key']    = $this->oauth_consumer_key;
            $params['auth']['oauth_consumer_secret'] = $this->oauth_consumer_secret;

            if (!empty($this->oauth_token) && !empty($this->oauth_token_secret)) {

                $params['auth']['oauth_token']        = $this->oauth_token;
                $params['auth']['oauth_token_secret'] = $this->oauth_token_secret;

            }

        }

        return $params;
    }

    /**
     * for DebugKit call
     */
    public function getLog() {

        return array('log' => array(), 'count' => array(), 'time' => array());

    }

    /**
     * check Xml response
     *
     * @param  string $src
     * @return true|false
     */
    protected function _isXml($src) {

        return preg_match('!^<\?xml!', $src);

    }

    /**
     * get Error Message
     *
     * @param string $src
     * @param string
     */
    protected function _getOAuthError($src) {

        $xml = new Xml($src);
        $result = $xml->toArray();

        return !empty($result['Hash']['error']) ? $result['Hash']['error'] : 'Error';
    }

    // ====================================================
    // == Search API Methods
    // ====================================================

    /**
     * search
     *
     * @param array  $params
     *     lang:        Optional: Restricts tweets to the given language, given by an ISO 639-1 code.
     *     locale:      Optional. Specify the language of the query you are sending (only ja is currently effective).
     *                            This is intended for language-specific clients and the default should work in the majority of cases.
     *     max_id:      Optional. Returns tweets with status ids less than the given id.
     *     q:           Optional. The text to search for.  See the example queries section for examples of the syntax supported in this parameter
     *     rpp:         Optional. The number of tweets to return per page, up to a max of 100.
     *     page:        Optional. The page number (starting at 1) to return, up to a max of roughly 1500 results (based on rpp * page. Note: there are pagination limits.
     *     since:       Optional. Returns tweets with since the given date.  Date should be formatted as YYYY-MM-DD
     *     since_id:    Optional. Returns tweets with status ids greater than the given id.
     *     geocode:     Optional. Returns tweets by users located within a given radius of the given latitude/longitude.
     *                            The location is preferentially taking from the Geotagging API, but will fall back to their Twitter profile.
     *                            The parameter value is specified by "latitide,longitude,radius", where radius units must be specified as either "mi" (miles) or "km" (kilometers).
     *                            Note that you cannot use the near operator via the API to geocode arbitrary locations; however you can use this geocode parameter to search near geocodes directly.
     *     show_user:   Optional. When true, prepends "<user>:" to the beginning of the tweet. This is useful for readers that do not display Atom's author field. The default is false.
     *     until:       Optional. Returns tweets with generated before the given date.  Date should be formatted as YYYY-MM-DD
     *     result_type: Optional. Specifies what type of search results you would prefer to receive.
     *         o Valid values include:
     *             + mixed: In a future release this will become the default value. Include both popular and real time results in the response.
     *             + recent: The current default value. Return only the most recent results in the response.
     *             + popular: Return only the most popular results in the response.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-search
     */
    public function search($params = array()) {

        $url    = sprintf('http://search.twitter.com/search.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('q' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * trends
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-trends
     */
    public function trends($params = array()) {

        $url    = sprintf('http://search.twitter.com/trends.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('q' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * trends/current
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-trends-current
     */
    public function trends_current($params = array()) {

        $url    = sprintf('http://search.twitter.com/trends/current.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('exclude' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * trends/daily
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-trends-daily
     */
    public function trends_daily($params = array()) {

        $url    = sprintf('http://search.twitter.com/trends/daily.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('date' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * trends/weekly
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-trends-weekly
     */
    public function trends_weekly($params = array()) {

        $url    = sprintf('http://search.twitter.com/trends/weekly.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('date' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Timeline Methods
    // ====================================================

    /**
     * statuses/public_timeline
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-public_timeline
     */
    public function statuses_public_timeline($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/public_timeline.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/home_timeline
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-home_timeline
     */
    public function statuses_home_timeline($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/home_timeline.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/friends_timeline
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-friends_timeline
     */
    public function statuses_friends_timeline($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/friends_timeline.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/user_timeline
     *
     * @param array  $params
     *     id.          Optional. Specifies the ID or screen name of the user for whom to return the user_timeline.
     *     user_id.     Optional. Specfies the ID of the user for whom to return the user_timeline. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *     screen_name. Optional. Specfies the screen name of the user for whom to return the user_timeline. Helpful for disambiguating when a valid screen name is also a user ID.
     *     since_id.    Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.      Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.       Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.        Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-user_timeline
     */
    public function statuses_user_timeline($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/user_timeline.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/mentions
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-mentions
     */
    public function statuses_mentions($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/mentions.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/retweeted_by_me
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-retweeted_by_me
     */
    public function statuses_retweeted_by_me($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/retweeted_by_me.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/retweeted_to_me
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-retweeted_to_me
     */
    public function statuses_retweeted_to_me($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/retweeted_to_me.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/retweets_of_me
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-retweets_of_me
     */
    public function statuses_retweets_of_me($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/retweets_of_me.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Status Methods
    // ====================================================

    /**
     * statuses/show
     *
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0show
     */
    public function statuses_show($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/show/%s.json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/update
     *
     * @param array  $params
     *      status. Required.  The text of your status update. URL encode as necessary.
     *                         Statuses over 140 characters  will cause a 403 error to be returned from the API.
     *                         Statuses have the same text as a recently posted status from the same user will also cause a 403 error to be returned from the API.
     *                         When a 403 is returned due to shortened text or duplicate statuses, the response body will contain details on why the tweet was rejected.
     *      in_reply_to_status_id. Optional. The ID of an existing status that the update is in reply to.
     *      lat.   Optional. The location's latitude that this tweet refers to.
     *                       The valid ranges for latitude is -90.0 to +90.0 (North is positive) inclusive.
     *                       This parameter will be ignored if outside that range, if it is not a number, if geo_enabled is disabled, or if there not a corresponding long parameter with this tweet.
     *      long.  Optional. The location's longitude that this tweet refers to.
     *                       The valid ranges for longitude is -180.0 to +180.0 (East is positive) inclusive.
     *                       This parameter will be ignored if outside that range, if it is not a number, if geo_enabled is disabled, or if there not a corresponding lat parameter with this tweet.
     *      place_id.            Optional. The place to attach to this status update.  Valid place_ids can be found by querying geo/reverse_geocode.
     *      display_coordinates. Optional. By default, geo-tweets will have their coordinates exposed in the status object (to remain backwards compatible with existing API applications).
     *                                     To turn off the display of the precise latitude and longitude (but keep the contextual location information), pass display_coordinates=false on the status update.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0update
     */
    public function statuses_update($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/update.json');
        $method = 'POST';

        if (is_string($params)) {

            $params = array('status' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/destroy
     *
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0destroy
     */
    public function statuses_destroy($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/destroy/%s.json', $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/retweet
     *
     * @param string $id Required.  The numerical ID of the tweet you are retweeting.
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-retweet
     */
    public function statuses_retweet($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/retweet/%s.json', $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/retweets
     *
     * @param string $id Required.  The numerical ID of the tweet you want the retweets of.
     * @param array  $params
     *      count.  Optional.  Specifies the number of retweets to retrieve. May not be greater than 100.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-retweets
     */
    public function statuses_retweets($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/retweets/%s.json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET /statuses/:id/retweeted_by.format
     *
     * @param string $id Required. The id of the status
     * @param array  $params
     *      count. Optional.  Indicates number of retweeters to return per page, with a maximum 100 possible results.
     *      page.  Optional.  Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-statuses-id-retweeted_by
     */
    public function get_statuses_id_retweet_by($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/%s/retweeted_by..json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET /statuses/:id/retweeted_by/ids.format
     *
     * @param string $id Required. The id of the status
     * @param array  $params
     *      count. Optional.  Indicates number of retweeters to return per page, with a maximum 100 possible results.
     *      page.  Optional.  Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-statuses-id-retweeted_by-ids
     */
    public function get_statuses_id_retweeted_by_ids($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/statuses/%s/retweeted_by/ids.format', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == User Methods
    // ====================================================

    /**
     * users/show
     *
     * @param string $id The ID or screen name of a user.
     * @param array  $params
     *      user_id.     Specfies the ID of the user to return. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name. Specfies the screen name of the user to return. Helpful for disambiguating when a valid screen name is also a user ID.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users%C2%A0show
     */
    public function users_show($id = null, $params = array()) {

        if (!empty($id)) {
            $id = '/' . $id;
        }

        $url    = sprintf('http://api.twitter.com/1/users/show%s.json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * users/lookup
     *
     * @param array  $params
     *      user_id.     Specfies the ID of the user to return.
     *      screen_name. Specfies the screen name of the user to return.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users-lookup
     */
    public function users_lookup($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/users/lookup.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * users/search
     *
     * @param array  $params
     *      q.         Required. The query to run against people search.
     *      per_page.  Optional. Specifies the number of statuses to retrieve. May not be greater than 20.
     *      page.      Optional. Specifies the page of results to retrieve.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users-search
     */
    public function users_search($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/users/search.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('q' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * users/suggestions
     *
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-users-suggestions
     */
    public function users_suggestions($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/users/suggestions.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * users/suggestions/:slug
     *
     * @param string $slug
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-users-suggestions-category
     */
    public function users_suggestions_category($slug, $params = array()) {

        if (empty($slug)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/users/suggestions/%s.json', $slug);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/friends
     *
     * @param array  $params
     *      id.           Optional. The ID or screen name of the user for whom to request a list of friends.
     *      user_id.      Optional. Specfies the ID of the user for whom to return the list of friends. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name.  Optional. Specfies the screen name of the user for whom to return the list of friends. Helpful for disambiguating when a valid screen name is also a user ID.
     *      cursor.       Optional. Breaks the results into pages. A single page contains 100 users. This is recommended for users who are following many users.
     *                              Provide a value of  -1 to begin paging. Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0friends
     */
    public function statuses_friends($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/friends.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * statuses/followers
     *
     * @param array  $params
     *      id.           Optional. The ID or screen name of the user for whom to request a list of friends.
     *      user_id.      Optional. Specfies the ID of the user for whom to return the list of friends. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name.  Optional. Specfies the screen name of the user for whom to return the list of friends. Helpful for disambiguating when a valid screen name is also a user ID.
     *      cursor.       Optional. Breaks the results into pages. A single page contains 100 users. This is recommended for users who are following many users.
     *                              Provide a value of  -1 to begin paging. Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0followers
     */
    public function statuses_followers($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/statuses/followers.json');
        $method = 'GET';

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == List Methods
    // ====================================================

    /**
     * POST lists (create)
     *
     * @param string $user
     * @param array  $params
     *      name.         Required. The name of the list you are creating.
     *      mode.         Optional. Whether your list is public or private. Values can be public or private. Lists are public by default if no mode is specified.
     *      description.  Optional. The description of the list you are creating.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-lists
     */
    public function post_lists($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists.json', $user);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * POST lists id (update)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     *      name.         Required. The name of the list you are creating.
     *      mode.         Optional. Whether your list is public or private. Values can be public or private. Lists are public by default if no mode is specified.
     *      description.  Optional. The description of the list you are creating.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-lists-id
     */
    public function post_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET lists (index)
     *
     * @param string $user
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-lists
     */
    public function get_lists($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET lists id (show)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-id
     */
    public function get_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * DELETE list id (destroy)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-DELETE-list-members
     */
    public function delete_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'POST';

        $params['_method'] = 'DELETE';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list statuses
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *      since_id.  Optional.  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *      max_id. Optional.  Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *      per_page.  Optional.  Specifies the number of statuses to retrieve. May not be greater than 200.
     *      page. Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_statuses($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s/statuses.json', $user, $list_id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list memberships
     *
     * @param string $user
     * @param array  $params
     *      cursor. Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                        Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_memberships($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/memberships.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list subscriptions
     *
     * @param string $user
     * @param array  $params
     *      cursor. Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                        Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_subscriptions($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/subscriptions.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == List Members Methods
    // ====================================================

    /**
     * GET list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *      list_id.  Required. The id or slug of the list.
     *      cursor.   Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                          Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-members
     */
    public function get_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * POST list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params array('id' => user_id)
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-list-members
     */
    public function post_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * DELETE list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params array('id' => user_id)
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-DELETE-list-members
     */
    public function delete_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        $params['_method'] = 'DELETE';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list members id
     *
     * @param string $user
     * @param string $list_id
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-members-id
     */
    public function get_list_members_id($user, $list_id, $id, $params = array()) {

        if (empty($user) || empty($list_id) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members/%s.json', $user, $list_id, $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == List Subscribers Methods
    // ====================================================

    /**
     * GET /:user/:list_id/subscribers
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *      list_id.  Required. The id or slug of the list.
     *      cursor.   Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                          Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-subscribers
     */
    public function get_list_subscribers($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/subscribers.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * POST /:user/:id/subscribers
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-list-subscribers
     */
    public function post_list_subscribers($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/subscribers.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * DELETE /:user/:list_id/subscribers
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *      id. Required. The ID or screen name of the user to remove from the list.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-DELETE-list-subscribers
     */
    public function delete_list_subscribers($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id) || empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/subscribers.json', $user, $list_id);
        $method = 'POST';

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        $params['_method'] = 'DELETE';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET /:user/:list_id/subscribers/:id
     *
     * @param string $user
     * @param string $list_id
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-subscribers-id
     */
    public function get_list_subscribers_id($user, $list_id, $id, $params = array()) {

        if (empty($user) || empty($list_id) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/subscribers/%s.json', $user, $list_id, $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Direct Message Methods
    // ====================================================

    /**
     * direct_messages
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-direct_messages
     */
    public function direct_messages($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/direct_messages.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * direct_messages/sent
     *
     * @param array  $params
     *     since_id.  Optional. Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *     max_id.    Optional. Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *     count.     Optional. Specifies the number of statuses to retrieve. May not be greater than 200.
     *     page.      Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-direct_messages%C2%A0sent
     */
    public function direct_messages_sent($params = array()) {

        $url    = sprintf('http://api.twitter.com/1/direct_messages/sent.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * direct_messages/new
     *
     * @param array  $params
     *     user:  Required.  The ID or screen name of the recipient user.
     *     text:  Required.  The text of your direct message.  Be sure to URL encode as necessary, and keep it under 140 characters.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-direct_messages%C2%A0new
     */
    public function direct_messages_new($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/direct_messages/new.json');
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * direct_messages/destroy
     *
     * @param string $id
     * @param array  $params
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-direct_messages%C2%A0destroy
     */
    public function direct_messages_destroy($id, $params = array()) {

        if (empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/direct_messages/destroy/%s.json', $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Friendship Methods
    // ====================================================

    /**
     * friendships/create
     *
     * @param array  $params
     *      id.          Required. The ID or screen name of the user to befriend.
     *      user_id.     Required. Specfies the ID of the user to befriend. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name. Required. Specfies the screen name of the user to befriend. Helpful for disambiguating when a valid screen name is also a user ID.
     *      follow.      Optional. Enable delivery of statuses from this user to the authenticated user's device;
     *                             tweets sent by the user being followed will be delivered to the caller of this endpoint's phone as SMSes.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships%C2%A0create
     */
    public function friendships_create($params = array()) {

        if (empty($params)) {
            return false;
        }

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        $id = '';

        if (!empty($params['id'])) {
            $id = '/' . $id;
            unset($params['id']);
        }

        $url    = sprintf('http://api.twitter.com/1/friendships/create%s.json', $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * friendships/destroy
     *
     * @param array  $params
     *      id.          Required. The ID or screen name of the user to befriend.
     *      user_id.     Required. Specfies the ID of the user to befriend. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name. Required. Specfies the screen name of the user to befriend. Helpful for disambiguating when a valid screen name is also a user ID.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships%C2%A0destroy
     */
    public function friendships_destroy($params = array()) {

        if (empty($params)) {
            return false;
        }

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        $id = '';

        if (!empty($params['id'])) {

            $id = '/' . $id;
            unset($params['id']);

        }

        $url    = sprintf('http://api.twitter.com/1/friendships/destroy%s.json', $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * friendships/exists
     *
     * @param string $id
     * @param array  $params
     *      user_a.  Required.  The ID or screen_name of the subject user.
     *      user_b.  Required.  The ID or screen_name of the user to test for following.
     *
     * @return true|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships-exists
     */
    public function friendships_exists($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/friendships/exists.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * friendships/show
     *
     * @param string $id
     * @param array  $params
     *      source_id. The user_id of the subject user.
     *      source_screen_name. The screen_name of the subject user.
     *      target_id. The user_id of the target user.
     *      target_screen_name. The screen_name of the target user.
     *
     * @return true|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships-show
     */
    public function friendships_show($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/friendships/show.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * friendships/incoming
     *
     * @param string $id
     * @param array  $params
     *      cursor. Required. Breaks the results into pages. A single page contains 5000 identifiers. Provide a value of -1 to begin paging.
     *                        Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return true|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships-incoming
     */
    public function friendships_incoming($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/friendships/incoming.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * friendships/outgoing
     *
     * @param string $id
     * @param array  $params
     *      cursor. Required. Breaks the results into pages. A single page contains 5000 identifiers. Provide a value of -1 to begin paging.
     *                        Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return true|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friendships-outgoing
     */
    public function friendships_outgoing($params = array()) {

        if (empty($params)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/friendships/outgoing.json');
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Social Graph Methods
    // ====================================================

    /**
     * friends/ids
     *
     * @param array  $params
     *      id.           Optional. The ID or screen_name of the user to retrieve the friends ID list for.
     *      user_id.      Optional. Specfies the ID of the user for whom to return the friends list. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name.  Optional. Specfies the screen name of the user for whom to return the friends list. Helpful for disambiguating when a valid screen name is also a user ID.
     *      cursor.       Required. Breaks the results into pages. A single page contains 5000 identifiers. This is recommended for all purposes. Provide a value of -1 to begin paging.
     *                              Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friends%C2%A0ids
     */
    public function friends_ids($params = array()) {

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        $id = '';

        if (!empty($params['id'])) {
            $id = '/' . $id;
            unset($params['id']);
        }

        $url    = sprintf('http://api.twitter.com/1/friends/ids%s.json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * followers/ids
     *
     * @param array  $params
     *      id.           Optional. The ID or screen_name of the user to retrieve the friends ID list for.
     *      user_id.      Optional. Specfies the ID of the user for whom to return the friends list. Helpful for disambiguating when a valid user ID is also a valid screen name.
     *      screen_name.  Optional. Specfies the screen name of the user for whom to return the friends list. Helpful for disambiguating when a valid screen name is also a user ID.
     *      cursor.       Required. Breaks the results into pages. A single page contains 5000 identifiers. This is recommended for all purposes. Provide a value of -1 to begin paging.
     *                              Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *
     * @return array|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-followers%C2%A0ids
     */
    public function followers_ids($params = array()) {

        if (is_string($params)) {

            $params = array('id' => $params);

        }

        $id = '';

        if (!empty($params['id'])) {
            $id = '/' . $id;
            unset($params['id']);
        }

        $url    = sprintf('http://api.twitter.com/1/followers/ids%s.json', $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == Account Methods
    // ====================================================


    // ====================================================
    // == Favorite Methods
    // ====================================================


    // ====================================================
    // == Notification Methods
    // ====================================================

    // ====================================================
    // == Block Methods
    // ====================================================

    // ====================================================
    // == Spam Reporting Methods
    // ====================================================

    // ====================================================
    // == Saved Searches Methods
    // ====================================================

    // ====================================================
    // == OAuth Methods
    // ====================================================

    /**
     * Get OAuth Request Token
     *
     * @param  string $oauth_callback
     * @return array
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-request_token
     */
    public function oauth_request_token($oauth_callback = null) {

        $url    = 'http://api.twitter.com/oauth/request_token';
        $method = 'GET';

        // get Request param
        $params = $this->_buildRequest($url, $method);

        if (empty($oauth_callback)) {

            $oauth_callback = $this->oauth_callback;

        }

        if (!preg_match('!^https?://!', $oauth_callback)) {

            $oauth_callback = Router::url($oauth_callback, true);

        }

        // add oauth callback
        $params['auth']['oauth_callback'] = $oauth_callback;

        // request
        $response = $this->_request($params, false);

        if ($this->_isXml($response)) {

            return $this->_getOAuthError($response);

        }

        parse_str($response, $response);

        if (!empty($response['oauth_token'])) {
            $this->oauth_token = $response['oauth_token'];
        }

        return $response;
    }

    /**
     * Get Authorize URL
     *
     * @param  string $oauth_token
     * @return string
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authorize
     */
    public function oauth_authorize($oauth_token = '') {

        $url    = 'http://api.twitter.com/oauth/authorize';

        if (empty($oauth_token)) {
            $oauth_token = $this->oauth_token;
        }

        return $url . '?oauth_token=' . $oauth_token;
    }

    /**
     * Get Authenticate URL
     *
     * @param  string $oauth_token
     * @return string
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authenticate
     */
    public function oauth_authenticate($oauth_token = '') {

        $url    = 'http://api.twitter.com/oauth/authenticate';

        if (empty($oauth_token)) {
            $oauth_token = $this->oauth_token;
        }

        return $url . '?oauth_token=' . $oauth_token;
    }

    /**
     * get oauth access token
     *
     * @param  string $oauth_token
     * @param  string $oauth_verifier
     * @return array
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-access_token
     */
    public function oauth_access_token($oauth_token, $oauth_verifier) {

        $url    = 'http://api.twitter.com/oauth/access_token';
        $method = 'GET';

        // get Request param
        $params = $this->_buildRequest($url, $method);

        // add oauth param
        $params['auth']['oauth_token']    = $oauth_token;
        $params['auth']['oauth_verifier'] = $oauth_verifier;

        // request
        $response = $this->_request($params, false);

        if ($this->_isXml($response)) {

            return $this->_getOAuthError($response);

        }

        parse_str($response, $response);

        if (!empty($response['oauth_token'])) {
            $this->oauth_token = $response['oauth_token'];
        }

        if (!empty($response['oauth_token_secret'])) {
            $this->oauth_token_secret = $response['oauth_token_secret'];
        }

        return $response;
    }


    // ====================================================
    // == Local Trends Methods
    // ====================================================

    // ====================================================
    // == Geo methods
    // ====================================================

    // ====================================================
    // == Help Methods
    // ====================================================



}