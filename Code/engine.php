<?php

class app
{
	protected static $db = null;
	protected static $config = [
		'enable' => false,
		'google' => [
			'url' => "*****************************************************"
		],
		'iai' => [
			'url' => "*************",
			'login' => '*********',
			'password' => '**********',
		],
		'bc' => [
			'host' => '****************',
			'dbname' => '*************',
			'user' => '***********',
			'password' => '*************',			
		]
	];
	
	private $sth = [];	
	protected $sql = [];
	
	public function __construct()
	{
		self::$config['iai']['password'] = sha1(date('Ymd').sha1('***************'));
		
		$temp = sprintf("%s/temp.csv", __DIR__);
		$iai = sprintf("%s/iai.csv", __DIR__);

		if (file_exists($iai) && date("H", filemtime($iai)) == date('H'))
		{
			self::$config['enable'] = true;
		}
		else
		{
			if (file_put_contents($temp, file_get_contents(self::$config['google']['url'])))
			{
				if (filesize($temp) > 150000)
				{
					if (file_exists($iai))
					{
						unlink($iai);
					}
					rename($temp, $iai);
					self::$config['enable'] = true;
				}
			}
		}			
	}
	
	public function search($post = [])
	{
		if (self::$config['enable'])
		{
			if (isset($post['number']) && !empty($post['number']))
			{
				if ($client = new SoapClient(sprintf('%s/wsdl', self::$config['iai']['url']), ['location' => self::$config['iai']['url'], 'trace' => true ]))
				{
					$iai = [];
					$bc = [];
					foreach(array_map(function($v){return str_getcsv($v, ",");}, file(sprintf("%s/iai.csv", __DIR__))) as $k => $v)
					{
						if (isset($v[0]) && isset($v[1]))
						{
							$iai[$v[1]][] = trim($v[0]);
						}
					}
					$request = [
						'get' => [
							'authenticate' => [
								'userLogin' => self::$config['iai']['login'],
								'authenticateKey' => self::$config['iai']['password']
							],
							'params' => [
								'ordersSerialNumbers' => []
							]
						]
					];
					$request['get']['params']['ordersSerialNumbers'][] = $post['number'];
					$response = $client->__call('get', $request);
					if (isset($response->Results) && !empty($response->Results))
					{
						foreach($response->Results[0]->orderDetails->productsResults as $k => $v)
						{
							if (array_key_exists(trim($v->productId), $iai))
							{
								
								$this->sql[sprintf('bc%s', $k)] = sprintf('**********
								*****************************************************
								*****************************************************
								*****************************************************
								*****************************************************', implode("','", $iai[$v->productId]));
								
								foreach($this->query(sprintf('bc%s', $k), []) as $key => $value)
								{
									$bc[] = [
										'number' => $post['number'],
										'location' => $value->{'Bin'},
										'name' => $v->productName,
										'count' => (float) $value->{'Quantity'}
									];
								}					
							}
						}
						
						return $bc;
					}
					else
					{
						return ['msg' => $response->errors->faultString];
					}
				}
				else
				{
					return ['msg' => "Błąd połączenia z IAI!"];
				}
			}
			else
			{
				return ['msg' => "Podaj numer zamówienia!"];
			}
		}
		else
		{
			return ['msg' => "Brak pliku CSV z danymi!"];
		}
	}

	protected function query($name, $args=[])
	{
		if (empty(self::$db))
		{ 
			self::$db = new PDO(sprintf("sqlsrv:server=%s;Database=%s;", self::$config['bc']['host'], self::$config['bc']['dbname']), self::$config['bc']['user'], self::$config['bc']['password'], [ PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
			self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		if (!array_key_exists($name, $this->sql) && isset(self::$db))
		{
			throw new Exception(sprintf("PL: Zapytanie %s nie istnieje w kodzie!\r\nEN: The query %s does not exist in the code!", $name, $name));				
		}

		if (!array_key_exists($name, $this->sth))
		{
			$this->sth[$name] = self::$db->prepare($this->sql[$name]);
		}
		
		foreach ($args as $key => $value)
		{
			$type = PDO::PARAM_STR;
			switch (gettype($value))
			{
				case 'boolean': $type = PDO::PARAM_BOOL; break;
				case 'integer': $type = PDO::PARAM_INT; break;
				case 'NULL': $type = PDO::PARAM_NULL; break;
				default:
					break;
			}
			$this->sth[$name]->bindValue($key, $value, $type);
		}		
		$this->sth[$name]->execute();
		$result = [];
		
		if (preg_match('/^[^A-Z_]*(SELECT|SHOW)[^A-Z_]/i', $this->sql[$name]))
		{
			while (($object = $this->sth[$name]->fetchObject())) $result[] = $object;
		}
		else
		{
			$object = (object)['count' => $this->sth[$name]->rowCount()];
			if (preg_match('/^[^A-Z_]*(INSERT|REPLACE)[^A-Z_]/i', $this->sql[$name])) $object->id = self::$db->lastInsertId();
			$result[] = $object;
		}
		return $result;
	}	
	
}

$app = new app;

if (isset($_POST) && !empty($_POST))
{
	echo json_encode($app->search($_POST));
}