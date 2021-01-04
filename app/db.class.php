<?php
function arr_sql($tab,$run,$arr){
   //unset($arr['id']); 
   $k =array_keys($arr);
   if($run == 'insert'){	  
    $sql = "insert into `{$tab}`(".join(',',$k).")values(:".join(',:',$k).")";
   }else{ 
	//$k =array_keys($arr);
    foreach($k as $v){
	   $s[] =  $v.'=:'.$v;
	}
    $sql = "update `{$tab}` set ".join(',',$s)." where id=:id";
   }     
  return $sql;
}
class Db
{
        private $conn;
        private $qxId;
        private $ret;
        
        function __construct()
        {              
			try{
				if(DB_TYPE == 'mysql'){                    
				   $this->conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';port='.DB_PORT.';charset=utf8',DB_USER,DB_PASS); 
				}else{				 
			       $this->conn = new PDO('sqlite:'.DB_NAME); 
				}
			 }			
			catch(Exception $errinfo){
				die ("PDO Connection faild.(可能空间不支持pdo，详细错误信息：)".$errinfo);
			}

        }
        
        /*读取*/
        function getdata($sql,$params=array())
        {
            $bind=$this->conn->prepare($sql);
            $arrKeys=array_keys($params);
            foreach($arrKeys as $row)
            {
				if(strpos($sql,"like")>-1){
				  $bind->bindValue(":".$row,'%'.$params[$row].'%');
				}else{
                  $bind->bindValue(":".$row,$params[$row]);
				}
            }
            $bind->execute();// or die('sql error:'.$sql);
            $result=$bind->fetchAll(PDO::FETCH_ASSOC);            
            return $result;
        }

		function getline($sql,$params=array()){
		    $result = $this->getdata($sql,$params);
			return @$result[0];
		}

        function total($tab_name,$tj='')//求总记录数目
           {
             $bind = $this->conn->prepare('SELECT count(id) as c FROM '.$tab_name.' '.$tj);
             $bind->execute();
             $result = $bind->fetchAll();
             return $result[0]['c'];
           }        
        /*添加,修改需调用此方法*/
        function runsql($sql,$params=array())
        {  
            $bind=$this->conn->prepare($sql);
            $arrKeys=array_keys($params); 
            foreach($arrKeys as $row)
            {
				 
                $bind->bindValue(":".$row,$params[$row]);
                
            }		 
            $a = $bind->execute();			 
			if(strpos($sql,"insert")>-1){
			   return $this->conn->lastInsertId();
			}else{
              return $a;
			}
        }
}