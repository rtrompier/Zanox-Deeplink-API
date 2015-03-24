<?php
    class ZanoxDeepLink{

        // store form data
        private $connectFormData;
        private $linkFormData;

        // advertiser ID (not always needed)
        private $zanoxAdvertiser;

        function __construct($login, $password, $zanoxAdspace, $zanoxAdvertiser=null) {
            if(is_null($login) || $login == ''){
                throw new Exception('Login can not be null or empty');
            }
            if(is_null($password) || $password == ''){
                throw new Exception('Password can not be null or empty');
            }
            if(is_null($zanoxAdspace) || $zanoxAdspace == ''){
                throw new Exception('Adspace can not be null or empty');
            }

            $this->connectFormData = array(
                'loginForm.loginViaUserAndPassword' => 'true',
                'loginForm.userName' => $login,
                'loginForm.password' => $password
            );

            $this->linkFormData = array(
                'sLanguage' => '1',
                'network' => 'zanox',
                'm4n_zone_id' => '',
                'm4n_username' => '',
                'm4n_password' => '',
                'zanox_adspaces' => $zanoxAdspace,
                'zanox_zpar0' => '',
                'zanox_zpar1' => '',
                'url' => '',
                'submit' => 'Get deeplink'
            );

            $this->zanoxAdvertiser = $zanoxAdvertiser;
        }

        public function getDeepLink($url){

            // make sure we have a connection to the generator
            $this->connect();

            // go fetch the link
            $this->linkFormData["url"] = $url;
            return $this->fetchDeepLink();
        }

        private function connect(){

            // prepare curl request for login form
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://auth.zanox.com/connect/login?appid=A5B83584B42A666E5309");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->preparePostFields($this->connectFormData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

            // do the curl request without output
            ob_start();
            $buf = curl_exec($ch);
            $header = curl_getinfo($ch);
            $redirectUrl = $header['redirect_url'];
            ob_end_clean();
            curl_close ($ch);
            unset($ch);

            // connection error
            if($redirectUrl == ""){
                if( preg_match('/<div class="pageErrorMessage">([^<]+)<\/div>/', $buf, $matches) ){
                    throw new Exception( trim(strip_tags($matches[1])) );
                }
                else {
                    throw new Exception("Connection error");
                }
            }

            // OK, now let's find the token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookieZanoxDeeplink");
            curl_setopt($ch, CURLOPT_URL,$redirectUrl);

            // do the curl request without output
            ob_start();
            curl_exec ($ch);
            ob_end_clean();
            curl_close ($ch);

        }

        private function fetchDeeplink(){

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/cookieZanoxDeeplink");
            curl_setopt($ch, CURLOPT_URL,"http://toolbox.zanox.com/deeplink/");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->preparePostFields($this->linkFormData));

            $buf = curl_exec ($ch);
            curl_close ($ch);

            //Check if we need to specify the program ID
            if( preg_match("/<select name='zx_advertiser'>(.*)<\/select>/", $buf, $matches) ){

                // we need to specify the advertiser ID in the constructor
                if( is_null($this->zanoxAdvertiser) )
                    throw new Exception("More than 1 program was found");

                // extract all programs offered to get a match
                preg_match_all("/<option value='([0-9]+)' >([0-9]+)[^<]+<\/option>/", $matches[1], $m);
                $options = array();
                foreach($m[2] as $k=>$v)
                    $options[ $v ] = $m[1][$k];

                // advertiser ID is not available for this URL
                if( !isset($options[ $this->zanoxAdvertiser ]) )
                    throw new Exception("Advertiser ID specified not available");

                // try again now with the advertiser ID!
                $this->linkFormData['zx_advertiser'] = $options[ $this->zanoxAdvertiser ];
                return $this->fetchDeepLink();
            }

            //Check if error
            if( preg_match("/<div class='error'[^>]+\>([^<]*)<\/div>/", $buf, $matches) ){
                throw new Exception($matches[1]);
            }

            //Return the deeplink
            if( preg_match("/<input type='text' id='result_url'[^>]+value='([^']+)'/", $buf, $matches) ){
                return $matches[1];
            }

            return null;
        }

        private function preparePostFields($array) {
            $params = array();

            foreach ($array as $key => $value) {
                $params[] = $key . '=' . urlencode($value);
            }

            return implode('&', $params);
        }
    }
?>