<?php
/*
Author:CplusHua
URI:http://weibo.com/sdnugonghua
Blog:http://hua.219.me
*/
Class PHPFetionRobot{
	private $tel;
	private $pwd;
	private $cookie;
	static 	$logintime;
	function __construct ($tel,$pwd){
		$this->tel=$tel;
		$this->pwd=$pwd;
	}
	function cookielogin(){
		$this->cookie=$this->readCookie();
	}
	function login($tel=null,$pwd=null){
		if(null==$tel||null==$pwd){
			$tel=$this->tel;
			$pwd=$this->pwd;
		} 
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL=> 'http://f.10086.cn/im5/',
			CURLOPT_REFERER=>'http://f.10086.cn/wap2.jsp',
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_HEADER=>true,
			CURLOPT_POST=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17'
		);
		$result=$this->exec($option);
		preg_match_all('/Location:\s{1}(.*)\n/', $result, $matches);
		$url=$matches[1][0];
		preg_match_all('/Set-Cookie:\s(UUID.*;)\spath.*\nSet-Cookie:\s(JSESSIONID.*;)\spath.*\n/',$result,$matches);//print_r($matches);
		$this->cookie=$matches[1][0].' '.$matches[2][0].' path=/; HttpOnly; ';
		$post_data ='m='.$tel.'&pass='.$pwd.'&captchaCode=&checkCodeKey=null'; 
		$option = array(
			CURLOPT_URL=> 'http://f.10086.cn/im5/login/loginHtml5.action?t='.time().'780', 
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER=>$url,//'http://f.10086.cn/im5/login/login.action',
			CURLOPT_POST=>true,
			CURLOPT_POSTFIELDS=>$post_data,
			CURLOPT_HEADER=>true,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
		);
		$result=$this->exec($option);  //echo $result;
		preg_match_all('/({.*})/', $result, $data); //print_r($data);
		preg_match_all('/Set-Cookie:\s(.*)\sHttpOnly\s\n/', $result, $matches);
		$this->cookie.='HttpOnly';
		foreach ($matches[1] as  $value) {
			$this->cookie.='; '.$value;
		}
		//echo $this->cookie;
		$user_info=json_decode($data[1][0]);
		//print_r($user_info);
		$this->saveCookie($this->cookie);//echo $this->cookie;
	}
	function getonlineuser(){//推荐在线好友
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL=>'http://f.10086.cn/im5/index/onlineUsers.action?t='.time().'017',
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER =>'http://f.10086.cn/im5/login/login.action' , 
			CURLOPT_POST=>true,
			CURLOPT_POSTFIELDS=>'gender=2',
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
		);
		$result=$this->exec($option);
		return  $result;
	}
	function sendMSG($fromIdUser,$msg){//通过ID直接发消息
		$post_data='touserid='.$fromIdUser.'&msg='.$msg;//echo $post_data;
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL =>'http://f.10086.cn/im5/chat/sendNewMsg.action' ,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER=>'http://f.10086.cn/im5/login/login.action?mnative=0&t='.time().'561',
			CURLOPT_POST=>true,
			CURLOPT_HEADER=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_POSTFIELDS=>$post_data,
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
		);
		$result=$this->exec($option);
		if($result=='{"sendCode":"true","info":"消息发送成功"}') return true;
		else return false;
	}
	function getmsg(){//echo $this->cookie;
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL=>'http://f.10086.cn/im5/box/alllist.action?t='.time().'151' ,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER=>'http://f.10086.cn/im5/login/login.action?mnative=0&t='.time().'561',
			CURLOPT_POST=>false,
			CURLOPT_HEADER=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
			);
		$result=$this->exec($option);
		if(!empty($result)){
			if('{"result":false}'==$result&&$logintime<3) {//被踢了!
				$this->login($this->tel,$this->pwd);
				$logintime++;
				$this->getmsg();
			}	
			//这里写如果有消息了，怎么处理
			$msg=json_decode($result);
            return $msg->chat_messages;
		}
	}
	function getmsgover($msgid){
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL=>'http://f.10086.cn/im5/chat/queryNewMsg.action?t='.time().'151&_='.time().'151&idMsgs='.$msgid.'&t='.time().'151' ,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER=>'http://f.10086.cn/im5/login/login.action?mnative=0&t='.time().'561',
			CURLOPT_POST=>false,
			CURLOPT_HEADER=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
		);
		$result=$this->exec($option);
		if('{"returnCode":200}'==$result) return true;
		return false;
	}
	function sendSMS($aimtel,$msg){
		//需要将用户手机号码转为userid
		$userid=$this->teltouid($aimtel);
		$post_data='touserid='.$userid.'&msg='.$msg;//echo $post_data;
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL =>'http://f.10086.cn/im5/chat/sendNewMsg.action' ,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_REFERER=>'http://f.10086.cn/im5/login/login.action?mnative=0&t='.time().'561',
			CURLOPT_POST=>true,
			CURLOPT_HEADER=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_POSTFIELDS=>$post_data,
			CURLOPT_COOKIESESSION=>true,
			CURLOPT_COOKIE=>$this->cookie,
		);
		$result=$this->exec($option);
		if($result=='{"sendCode":"true","info":"消息发送成功"}') return true;
		else return false;
	}
	function teltouid($tel){
		$option = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_URL =>'http://f.10086.cn/im5/index/searchFriendsByQueryKey.action' ,
			CURLOPT_REFERER=>'http://f.10086.cn/im5/login/login.action?mnative=0&t='.time().'192',
			CURLOPT_POST=>true,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_POSTFIELDS=>'queryKey='.$tel,
			CURLOPT_HEADER=>false,
			CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
			CURLOPT_COOKIE=>$this->cookie,
			CURLOPT_COOKIESESSION=>true
		);
		$obj=json_decode($this->exec($option));//echo $obj->contacts[0]->idFetion; print_r($obj);
		//echo $obj->contacts[0]->idContact;
		return $obj->contacts[0]->idContact ;
	}
	function exec($option){
		$c=curl_init();
		curl_setopt_array($c,$option);
		$result=curl_exec($c);
		curl_close($c);
		return $result;
	}
	 function sae_saveCookie($string){
		$mmc=memcache_init();
	    if($mmc==false){
	        echo "mc init failed\n"; return 0;	    	
	    }
	    else
	    {
	        return memcache_set($mmc,$this->tel,$string);
	    }
	}
	function sae_readCookie(){
		$mmc=memcache_init();
	    if($mmc==false){
	        echo "mc init failed\n"; return 0;
	    }
	    else
	    {
	        echo $res=memcache_get($mmc,$this->tel);
                return $res;
	    }

	}
	function saveCookie($string){
		if(!empty($_SERVER['HTTP_APPNAME'])&&!empty($_SERVER['HTTP_APPVERSION'])) return $this->sae_saveCookie($string);
		$f=fopen($this->tel.'.txt', 'w');
		return fwrite($f, $string);
	}
	function readCookie(){
          if(isset($_SERVER['HTTP_APPNAME'])&&isset($_SERVER['HTTP_APPVERSION'])){  $this->cookie= $this->sae_readCookie(); return 1;}
		if(!file_exists($this->tel.'.txt')) $this->login();
		if(filesize($this->tel.'.txt')){
			$f=fopen($this->tel.'.txt', 'r');
			$cookie=fread($f, filesize($this->tel.'.txt'));
			if(!empty($cookie)) return $this->cookie=$cookie;
		}
	}
	 public function autoresponse($key){
		$header = array();
		$header[]= 'Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, text/html, * '. '/* ';  
		$header[]= 'Accept-Language: zh-cn ';  
		$header[]= 'User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17';  
		$header[]= 'Host: www.simsimi.com';  
		$header[]= 'Connection: Keep-Alive ';  
		$header[]= 'Cookie: JSESSIONID=A7527CE5DDDD8A79E093951ACD4FB3C1';
	
		$Ref="http://www.simsimi.com/talk.htm";
		$option= array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_URL => 'http://www.simsimi.com/func/req?msg='.$key.'&lc=ch',       
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_REFERER	=> $Ref,
			CURLOPT_COOKIESESSION=>true
		);
		$result=$this->exec($option);
		$Message = json_decode($result);
		if($Message->result=='100' && $Message->result != 'hi' && $Message->result !='' ){
			return $Message->response;
		}else{
			return '你说的什么？';
		}
	}
	
}
