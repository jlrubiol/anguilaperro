<?php

class Controllers_Author
{

	public $content;
	public $request;
	public $config;
	private $seed;
	private $time;
	private $timeout;

	public function __construct($request)
	{
		$this->request=$request;
		require_once ("/Model/generalModel.php");
		$config_file="../application/configs/config.ini";
		$this->config=readConfigFile($config_file, APPLICATION_ENV);
		$this->seed = $this->config['verify.signature'];
		$this->timeout = $this->config['verify.timeout'];
		$this->time = time();
	}

	public function loginAction($viewparams)
	{
		$this->content=renderView($this->request,$viewparams);
	}
	
	public function logoutAction($viewparams)
	{
		$this->content=renderView($this->request,$viewparams);
	}
	
	public function registerAction()
	{
		$viewparams=array();
		if($_POST){
			$user = new Entity_User();
			$user->setDisplay_name($_POST['display_name']);
			$user->setEmail($_POST['email']);
			$user->setName($_POST['name']);
			$user->setPassword($_POST['password']);
			$user->setTimestamp($this->time);
			$user->setToken($this->setToken($_POST['email']));
			$users = new Model_Users();
			$users->insertUser($user);
			$this->sendEmail($user);
// 			header("Location: /users");
		}
		$this->content=renderView($this->request,$viewparams);
	}

	public function logingmailAction($viewparams)
	{
		$config = array(
				'callbackUrl' => 'http://anguilaperro.org/author/gmailCallback',
				'siteUrl'      => 'https://accounts.google.com/o/oauth2/auth',							
				'consumerKey' => '78200168710.apps.googleusercontent.com',
				'consumerSecret' => 'Sa2FTOg6cmtGkfiqba8MUf44'
		);
		$consumer = new Zend_Oauth_Consumer($config);
		
		$token = $consumer->getRequestToken();		
		$_SESSION['GMAIL_REQUEST_TOKEN'] = serialize($token);
		$consumer->redirect();
	}
	
	public function gmailCallbackAction($viewparams)
	{
		$config = array(
				'callbackUrl' => 'http://anguilaperro.org/author/gmailCallback',
				'siteUrl'      => 'https://accounts.google.com/o/oauth2/auth',							
				'consumerKey' => '78200168710.apps.googleusercontent.com',
				'consumerSecret' => 'Sa2FTOg6cmtGkfiqba8MUf44'
		);
		$consumer = new Zend_Oauth_Consumer($config);
		 
		if (!empty($_GET) && isset($_SESSION['GMAIL_REQUEST_TOKEN'])) {
		    $token = $consumer->getAccessToken(
		                 $_GET,
		                 unserialize($_SESSION['GMAIL_REQUEST_TOKEN'])
		             );
		    $_SESSION['GMAIL_REQUEST_TOKEN'] = serialize($token);
		 
		    // Now that we have an Access Token, we can discard the Request Token
		    $_SESSION['GMAIL_REQUEST_TOKEN'] = null;
		} else {
		    // Mistaken request? Some malfeasant trying something?
		    echo ('Invalid callback request. Oops. Sorry.');

	
		}
	}

	public function __destruct()
	{
		$layoutparams=array('content'=>$this->content);
		echo renderLayout('login', $layoutparams);
	}
	
	private function setToken($email){
		return $token = md5($email.$this->time.$this->seed);
	}
	
	private function sendEmail($user){
		$config['server']=$this->config['smtp.server'];
		$config['ssl']=$this->config['smtp.ssl'];
		$config['port']=$this->config['smtp.port'];
		$config['auth']=$this->config['smtp.auth'];
		$config['username']=$this->config['smtp.username'];
		$config['password']=$this->config['smtp.password'];
		$config['urlActivate']= 'http://anguilaperro.local/author/verify/'.$user->getEmail().'/'.$user->getToken();
		$transport = new Zend_Mail_Transport_Smtp($config['server'], $config);
		
		
		$mail = new Zend_Mail();
		$mail->addTo($user->getEmail(), 'Test');
		
		$mail->setSubject('Activate your account');
		$mail->setBodyText("Activate your account ".$config['urlActivate']);
		$mail->send($transport);
	}
}
