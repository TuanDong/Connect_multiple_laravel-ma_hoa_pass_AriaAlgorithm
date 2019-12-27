<?php 


namespace App\Repositories\Contracts;

use Config;
use Closure;
use DB;
use Session;

abstract class BaseRepository {

	/**
	 * The Model instance.
	 *
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;
	public $_sMessage;

	/**
	* Connect Database, default is database user when login
	*
	* @param string $_sDatabase 
	* @var bool $_bStatusConnect
	* @return bool
	*/
	public function connectDb($_sDatabase = null){
		$_bStatusConnect = true;

		if(!$_sDatabase){
			if(env('DB_DATABASE')){
				$user_current = auth()->user();
				DB::disconnect(env('DB_DATABASE'));
				Config::set('database.connections.mysql', array(
                    'driver' 	=> 'mysql',
                    'host'		=> env('DB_HOST'),
                    'port' 		=> env('DB_PORT'),
                    'database'  => env('DB_DATABASE2').''.$user_current->COMPANY_CD,
                    'username' 	=> env('DB_USERNAME'),
                    'password' 	=> env('DB_PASSWORD'),
                    'charset' 	=> 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' 	=> '',
                ));
                DB::reconnect('mysql');
			}else{
				$_bStatusConnect = false;
			}

		}else{

			if(env('DB_DATABASE')){
				DB::disconnect(Session::get('DB_DATABASE'));
			}
			Config::set('database.connections.mysql', array(
                    'driver' 	=> 'mysql',
                    'host'		=> env('DB_HOST'),
                    'port' 		=> env('DB_PORT'),
                    'database'  => env('DB_DATABASE'),
                    'username' 	=> env('DB_USERNAME'),
                    'password' 	=> env('DB_PASSWORD'),
                    'charset' 	=> 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' 	=> '',
                ));

			DB::reconnect('mysql');
		}
		if(!DB::connection()->getDatabaseName()){
			$_bStatusConnect = false;
		}
		return $_bStatusConnect;
	}

	/**
	* run procedure
	* @param string $_sNameProc
	* @param array $_aDataOut
	* @param array 	$_aDataIn
	* @var 	 string $_sSql
	* @return DB || error message
	*/
	public function runProcedure($_sNameProc,$_aDataIn = null ,$_aDataOut = null )
	{
		try{
			$_sSql = 'CALL ' . $_sNameProc;

			// _aDataIn,_aDataOut != null
			if ($_aDataIn && $_aDataOut) {
				$_sSql = $_sSql . '(';

				foreach ($_aDataIn as $key => $value) {
					$_sSql = $_sSql . '?,';
				}

				foreach ($_aDataOut as $key => $value) {
					$_sSql = $_sSql .'@' . $value .',';
				}
					
				// delete char ,
				$_sSql = substr($_sSql, 0,strlen($_sSql)-1);
				$_sSql = $_sSql . ');';

			// _aDataIn != null
			}elseif ($_aDataIn) {

				$_sSql = $_sSql . '(';

				foreach ($_aDataIn as $key => $value) {
					$_sSql = $_sSql . '?,';
				}

				// delete char ,
				$_sSql = substr($_sSql, 0,strlen($_sSql)-1);
				$_sSql = $_sSql . ')';
			// _aDataOut != nul
			}elseif ($_aDataOut) {

				$_sSql = $_sSql . '(';

				foreach ($_aDataOut as $key => $value) {
					$_sSql = $_sSql .'@' . $value .',';
				}
					
				// delete char ,
				$_sSql = substr($_sSql, 0,strlen($_sSql)-1);
				$_sSql = $_sSql . ')';

			}
			
			// _aDataOut != nul
			if($_aDataOut){
				DB::statement($_sSql, $_aDataIn);

				$_sSql = 'select ';
				foreach ($_aDataOut as $key => $value) {
					$_sSql = $_sSql . '@'.$value . ' as '. $value .',';
				}
				$_sSql = substr($_sSql, 0,strlen($_sSql)-1);
				return DB::select($_sSql);

			}
			return DB::select($_sSql, $_aDataIn);
		}catch(\Exception $e){
			return $this->_sMessage = $e->getMessage();
		}
		
	}

	public static function runProcedureWithMultipleResult($procName, $parameters = null, $isExecute = false)
	{
	    $syntax = '';
	    for ($i = 0; $i < count($parameters); $i++) {
	        $syntax .= (!empty($syntax) ? ',' : '') . '?';
	    }
	    $syntax = 'CALL ' . $procName . '(' . $syntax . ');';
	    $pdo = DB::connection()->getPdo();
	    $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
	    $stmt = $pdo->prepare($syntax,[\PDO::ATTR_CURSOR=>\PDO::CURSOR_SCROLL]);
	    for ($i = 0; $i < count($parameters); $i++) {
	        $stmt->bindValue((1 + $i), $parameters[$i]);
	    }
	    $exec = $stmt->execute();
	    if (!$exec) return $pdo->errorInfo();
	    if ($isExecute) return $exec;

	    $results = [];
	    do {
	        try {
	            $results[] = $stmt->fetchAll(\PDO::FETCH_OBJ);
	        } catch (\Exception $ex) {

	        }
	    } while ($stmt->nextRowset());


	    if (1 === count($results)) return $results[0];
	    return $results;
	}


	/**
	* run sql
	* @param string $_sNameProc
	* @param string $_aData
	*/
	public function runSql($_sSql,$_aData= null)
	{	
		$_result = null;
		try{
			if($_aData){
				return DB::select($_sSql,$_aData);
			}else{

				$_aTempSql = explode(";",$_sSql);
				$_iCount =count($_aTempSql);

				if( $_iCount > 1 ){
					$_iCount = $_iCount - 1 ; 
				}
				
				for ($i=0; $i < $_iCount ; $i++) { 
					$_result = DB::select($_aTempSql[$i] . ";");
				}
				return $_result;
			}
		}catch(\Exception $e){
			return $this->_sMessage = $e->getMessage();
		}

		
	}

	/**
	*  mysql_real_escape_string
	* @param string $_sStr
	* @return string
	*/
	public function quoteString($_sStr)
	{
		return DB::connection()->getPdo()->quote($_sStr);
	}

	/**
	* generate sql procedure
	* @param string $_sNameProcedure
	* @param array $_aParam
	* @var string $_sSql
	**/
	public function getSqlQuery($_sNameProcedure,$_aParam)
	{
		$_sSql = 'CALL ' . $_sNameProcedure;

		$_sSql = $_sSql . '(';
		foreach ($_aParam as $key => $value) {
			$_sSql = $_sSql . "'".$value."'" .',';
		}

		// delete char ,
		$_sSql = substr($_sSql, 0,strlen($_sSql)-1);
		$_sSql = $_sSql . ')';

		return $_sSql;
	}

}
