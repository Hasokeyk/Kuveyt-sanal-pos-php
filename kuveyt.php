<?php 
    
    /*
        Hasan Yüksektepe
        hasanhasokeyk@hotmail.com
        16.09.2019
        Kuveyt Türk Sanal Pos
    */

    class kuveytSanalPos{

        public $url                     = 'https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelPayGate';
        public $confirmUrl              = 'https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelProvisionGate';
        public $APIVersion              = "1.0.0"; //Api versiyonu
        public $Type                    = "Sale"; //Ödeme türü satış
        public $instalment			    = "0";
        public $OkUrl                   = "/?durum=basarili";
        public $FailUrl                 = "/?durum=hata";
        public $CurrencyCode	        = "0949"; //TL islemleri için @değiştirme
        public $MerchantId              = ""; //MAĞAZA KODU
        public $CustomerId              = ""; //MÜŞTERİ NUMARASI
        public $UserName                = ""; //APİ KULLANICI ADI
        public $Password			    = ""; //APİ KULLANICI ŞİFRESİ
		public $TransactionSecurity 	= 3;
        
        //TEST CART NUMBER
        public  $testUrl        = 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelPayGate';
        public  $confirmTestUrl = 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelProvisionGate';
        private $testCartNumber = '4033602562020327';
        private $testCartCVV2   = '861';
        private $testCartMonth  = '01';
        private $testCartYear   = '20';
        //TEST CART NUMBER

        function odemeOnay($degerler){
            $xmlOlustur = $this->xmlOlustur($degerler,true);
            if($xmlOlustur['status'] == 'success'){
                $sonuc = $this->sorgu($xmlOlustur,true);
                if($sonuc['info']['http_code'] == 200){
                    return $this->sonuc($sonuc['data']);
                }else{
                    return [
                        'status' => 'danger',
                        'message' => 'Banka Sunucu Hatası',
                    ];
                }
            }else{
                return [
                    'status' => 'danger',
                    'message' => 'Xml Oluşturlamadı',
                ];
            }
        }

        function odemeYap($degerler){

            $kontrol = $this->degerKontrol($degerler);
            if($kontrol === true){
                $xmlOlustur = $this->xmlOlustur($degerler);
                if($xmlOlustur['status'] == 'success'){
                    $sonuc = $this->sorgu($xmlOlustur);
                    if($sonuc['info']['http_code'] == 200){
                        return $this->sonuc($sonuc['data']);
                    }else{
                        return [
                            'status' => 'danger',
                            'message' => 'Banka Sunucu Hatası',
                        ];
                    }
                }else{
                    return [
                        'status' => 'danger',
                        'message' => 'Xml Oluşturlamadı',
                    ];
                }
            }else{
                return [
                    'status' => 'danger',
                    'message' => 'Parametre Eksik "'.$kontrol.'"',
                ];
            }

        }

        function xmlOlustur($degerler=null,$onay=false){

            if($degerler!=null and is_array($degerler)){

                if(isset($degerler['test']) and $degerler['test']=='true'){
                    $this->CustomerId = '400235';
                    $this->MerchantId = '496';
                    $this->UserName = 'apitest'; 
                    $this->Password = 'api123';

                    $degerler['kartNo'] = $this->testCartNumber;
                    $degerler['kartCVV'] = $this->testCartCVV2;
                    $degerler['kartAy'] = $this->testCartMonth;
                    $degerler['kartYil'] = $this->testCartYear;
                    $degerler['tutar'] = 1;
                    $degerler['siparisNo'] = 'test';
                }

                $MerchantOrderId	= $degerler['siparisNo']??'KUV'.time();
                $tutar = (trim($degerler['tutar'])*100);
                //$tutar = 100;

                $HashedPassword     = base64_encode(sha1($this->Password,"ISO-8859-9"));
                
                if($onay == false){
                    
                    $HashData = base64_encode(sha1($this->MerchantId.$MerchantOrderId.$tutar.$this->OkUrl.$this->FailUrl.$this->UserName.$HashedPassword , "ISO-8859-9"));

                    $kartTuru 	= $degerler['kartNo'];
                    switch($kartTuru[0]){
                        case '4':
                            $kartTuru = 'Visa';
                        break;
                        case '5':
                            $kartTuru = 'MasterCard';
                        break;
						default:
							$kartTuru = 'Visa';
						break;
                    }

                    $postParametreleri = trim(
                        '<?xml version="1.0" encoding="ISO-8859-1"?>
                        <KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchemainstance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                            <APIVersion>'.$this->APIVersion.'</APIVersion>
                            <OkUrl>'.$this->OkUrl.'</OkUrl>
                            <FailUrl>'.$this->FailUrl.'</FailUrl>
                            <HashData>'.$HashData.'</HashData>
                            <MerchantId>'.$this->MerchantId.'</MerchantId>
                            <CustomerId>'.$this->CustomerId.'</CustomerId>
                            <UserName>'.$this->UserName.'</UserName>
                            <CardNumber>'.$degerler['kartNo'].'</CardNumber>
                            <CardExpireDateYear>'.$degerler['kartYil'].'</CardExpireDateYear>
                            <CardExpireDateMonth>'.$degerler['kartAy'].'</CardExpireDateMonth>
                            <CardCVV2>'.$degerler['kartCVV'].'</CardCVV2>
                            <CardHolderName>'.$degerler['kartSahibiAdi'].'</CardHolderName>
                            <CardType>'.$kartTuru.'</CardType>
                            <BatchID>0</BatchID>
                            <TransactionType>'.$this->Type.'</TransactionType>
                            <InstallmentCount>'.$this->instalment.'</InstallmentCount>
                            <Amount>'.$tutar.'</Amount>
                            <DisplayAmount>'.$tutar.'</DisplayAmount>
                            <CurrencyCode>'.$this->CurrencyCode.'</CurrencyCode>
                            <MerchantOrderId>'.$MerchantOrderId.'</MerchantOrderId>
                            <TransactionSecurity>'.$this->TransactionSecurity.'</TransactionSecurity>
                        </KuveytTurkVPosMessage>');
                        
                    return [
                        'status' => 'success',
                        'message' => 'Satış için XML Oluşturuldu',
                        'data' => $postParametreleri,
                        'test' => isset($degerler['test'])?'true':'false'
                    ];
                }else{

                    $HashData = base64_encode(sha1($this->MerchantId.$degerler['MerchantOrderId'].$degerler['tutar'].$this->UserName.$HashedPassword , "ISO-8859-9"));
					
                    $postParametreleri = trim(
                        '<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                            <APIVersion>'.$this->APIVersion.'</APIVersion>
                            <HashData>'.$HashData.'</HashData>
                            <MerchantId>'.$this->MerchantId.'</MerchantId>
                            <CustomerId>'.$this->CustomerId.'</CustomerId>
                            <UserName>'.$this->UserName.'</UserName>
                            <TransactionType>Sale</TransactionType>
                            <InstallmentCount>'.$this->instalment.'</InstallmentCount>
                            <CurrencyCode>'.$this->CurrencyCode.'</CurrencyCode>
                            <Amount>'.$degerler['tutar'].'</Amount>
                            <MerchantOrderId>'.$degerler['MerchantOrderId'].'</MerchantOrderId>
                            <TransactionSecurity>3</TransactionSecurity>
                            <KuveytTurkVPosAdditionalData>
                                <AdditionalData>
                                    <Key>MD</Key>
                                    <Data>'.$degerler['MD'].'</Data>
                                </AdditionalData>
                            </KuveytTurkVPosAdditionalData>
                        </KuveytTurkVPosMessage>');

                        return [
                            'status' => 'success',
                            'message' => 'Onay için XML Oluşturuldu',
                            'data' => $postParametreleri,
                            'test' => (isset($degerler['test']) and $degerler['test'] == 'true')?'true':'false'
                        ];

                }
            }else{
                return [
                    'status' => 'danger',
                    'message' => 'XML oluşturmak için değerler eksik',
                ];
            }

        }

        function sorgu($degerler,$onay=false){

            try {
                
                if($degerler['test'] == 'false'){
                    $url = $this->url;
                }else{
                    $url = $this->testUrl;
                }

                if($onay == true and $degerler['test'] == 'false'){
                    $url = $this->confirmUrl;
                }else if($onay == true and $degerler['test'] == 'true'){
                    $url = $this->confirmTestUrl;
                }

                $ch = curl_init();  
                curl_setopt($ch, CURLOPT_URL,$url); //Baglanacagi URL  
                curl_setopt($ch, CURLOPT_SSLVERSION, 0);//prevent Poddle attack, have to set 0 to use TLS instead of SSL3 
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST, true); //POST Metodu kullanarak verileri gönder  
                curl_setopt($ch, CURLOPT_HEADER, false); //Serverdan gelen Header bilgilerini önemseme.  
                curl_setopt($ch, CURLOPT_RETURNTRANSFER , true); //Sonucu otomatik yazdırma kapalı
                curl_setopt($ch, CURLOPT_POSTFIELDS, $degerler['data']);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml', 'Content-length: '. strlen($degerler['data'])) );
                //curl_setopt($ch, CURLOPT_INTERFACE, '176.43.55.129');
                //curl_setopt($ch, CURLOPT_INTERFACE, '91.208.199.110');
                //curl_setopt($ch, CURLOPT_INTERFACE, '37.230.104.118');
                curl_setopt($ch, CURLOPT_INTERFACE, '185.106.208.124');
                $data = curl_exec($ch); 
                $info = curl_getinfo($ch);
                curl_close($ch);

                return [
                    'data' => $data,
                    'info' => $info,
                ];

            }catch (Exception $e){
    			echo 'Genel Hata: ',  $e->getMessage(), "\n";
            }
            
        }

        function sonuc($data,$post=false,$onay=false){
            
            /*
                NOT: Bu bölümde 2 işlem yapılmaktadır.
                1- Kart bilgisi doğrulama
                2- Kart doğru ise ödeme onaylama

                SENARYO
                GİRİLEN KART BİLGİLERİ BANKAYA GÖNDERİLİR. BANKA KARTI ONAYLAR. DÖNEN ORDER ID VE MD SONUÇLARI TEKRAR BANKAYA
                ONAYA SUNULUR BANKA DA ÖDEME ONAYINI VETA REDDİNİ VERİR.

            */

            preg_match('|<input(.*?)name="PaReq"(.*?)value="(.*?)">|is',urldecode($data),$sonuc);
            if(isset($sonuc[3])){
                return $result = [
                    'status' => 'redirect',
                    'data' => $data
                ];
            }else{
                $sonuc = json_decode(json_encode(@simplexml_load_string(urldecode($data))));
                if($post == true and $onay == false){

                    if(isset($sonuc->ResponseCode) and $sonuc->ResponseCode == '00'){

                        $odemeOnay = $this->odemeOnay([
                            'tutar' => $sonuc->VPosMessage->Amount,
                            'MD' => $sonuc->MD,
                            'MerchantOrderId' => $sonuc->MerchantOrderId,
                            'test' => $sonuc->MerchantOrderId=='test'?'true':'false',
                        ]);
                        
                        $odemeSonuc = $this->sonuc($odemeOnay['data'],true,true);
                        if($odemeSonuc['ResponseCode'] == '00'){
                            return $result = [
                                'status' => 'success',
                                'message' => 'Ödeme İşlemi Onaylandı. Teşekkür ederiz.',
                                'ResponseCode' => $odemeSonuc['ResponseCode']
                            ];
                        }else{
                            return $result = [
                                'status' => 'danger',
                                'message' => $odemeSonuc['message'],
                                'ResponseCode' => $odemeSonuc['ResponseCode']
                            ];
                        }
                    }else{
                        return $result = [
                            'status' => 'danger',
                            'message' => $sonuc->ResponseMessage,
                            'ResponseCode' => $sonuc->ResponseCode
                        ];
                    }

                }else if($post == true and $onay ==true){

                    if($sonuc->ResponseCode == '00'){
                        return $result = [
                            'status' => 'success',
                            'message' => 'Ödeme İşlemi Tamamlandı',
                            'ResponseCode' => $sonuc->ResponseCode
                        ];
                    }else{
                        return $result = [
                            'status' => 'danger',
                            'message' => $sonuc->ResponseMessage,
                            'ResponseCode' => $sonuc->ResponseCode
                        ];
                    }

                }else{
                    return $result = [
                        'status' => 'redirect',
                        'data' => $data
                    ];
                }
            }
        }

        function degerKontrol($degerler){

            $zorunlu = ['tutar','kartNo','kartAy','kartYil','kartCVV','kartSahibiAdi'];
            foreach($zorunlu as $key){
                 if(!$this->inKeyArray($key,$degerler)){
                     return $key;
                 }
            }
 
            return true;
        }

        function inKeyArray($igne,$samanlik){

            foreach($samanlik as $key => $deger){
                if($key == $igne){
                    return true;
                }
            }

            return false;

        }

    }

?>      