<?php
    class ZanoxDeepLink{
        var $dataConnection;
        var $dataDeepLink;
        var $redirectUrl;

        function __construct($login, $password, $zanoxAdspace, $zanoxAdvertiser) {
            if(is_null($login) || $login == ''){
                throw new Exception('Login can not be null or empty');
            }
            if(is_null($password) || $password == ''){
                throw new Exception('Password can not be null or empty');
            }
            if(is_null($zanoxAdspace) || $zanoxAdspace == ''){
                throw new Exception('Adspace can not be null or empty');
            }

            $this->dataConnection = array(
                'loginForm.loginViaUserAndPassword' => 'true',
                'loginForm.userName' => $login,
                'loginForm.password' => $password
            );

            $this->dataDeepLink = array(
                'sLanguage' => '1',
                'network' => 'zanox',
                'm4n_zone_id' => '',
                'm4n_username' => '',
                'm4n_password' => '',
                'zanox_adspaces' => $zanoxAdspace,
                'zx_advertiser' => $zanoxAdvertiser,
                'zanox_zpar0' => '',
                'zanox_zpar1' => '',
                'url' => '',
                'submit' => 'Récupérer le lien profond'
            );
        }

        public function getDeepLink($url){
            $this->dataDeepLink[url] = $url;

            $postConnection = $this->preparePostFields($this->dataConnection);
            $postDeeplink = $this->preparePostFields($this->dataDeepLink);

            $this->connection($postConnection);
            $this->getToken();
            $deeplink = $this->parseDeeplink($postDeeplink);

            return $deeplink;
        }

        private function preparePostFields($array) {
            $params = array();

            foreach ($array as $key => $value) {
                $params[] = $key . '=' . urlencode($value);
            }

            return implode('&', $params);
        }

        private function connection($data){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://auth.zanox.com/connect/login?appid=A5B83584B42A666E5309");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

            ob_start();      // prevent any output
            $tmp = curl_exec($ch); // execute the curl command
            $header = curl_getinfo($ch);
            $this->redirectUrl = $header['redirect_url'];
            ob_end_clean();  // stop preventing output
            curl_close ($ch);
            unset($ch);

            if($this->redirectUrl == ""){
                throw new Exception($this->getError($tmp));
            }
        }

        private function getToken(){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookieZanoxDeeplink");
            curl_setopt($ch, CURLOPT_URL,$this->redirectUrl);

            ob_start();      // prevent any output
            curl_exec ($ch);
            ob_end_clean();  // stop preventing output
            curl_close ($ch);
        }

        private function parseDeeplink($postDeeplink){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/cookieZanoxDeeplink");
            curl_setopt($ch, CURLOPT_URL,"http://toolbox.zanox.com/deeplink/");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postDeeplink);

            $buf = curl_exec ($ch);
            curl_close ($ch);

            $doc = new DOMDocument();
            $doc->loadHTML($buf);
            $xpath = new DOMXPath($doc);

            //Check if error
            $errors = $xpath->query('//div[@class="error"]');
            if($errors->length > 0){
                throw new Exception($errors->item(0)->textContent);
            }

            //Return the deeplink
            $elements = $xpath->query('//input[@id="result_url"]/@value');
            return $elements->item(0)->textContent;
        }

        private function getError($buf){
            $doc = new DOMDocument();
            $doc->loadHTML($buf);
            $xpath = new DOMXPath($doc);
            $elements = $xpath->query('//div[@class="pageErrorMessage"]');

            return $elements->item(0)->textContent;
        }
    }
?>