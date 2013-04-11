<?php

    //todo: svaki fail insertat u posebnu tablicu koja ce podizat alarm
	
    //database configuration file
    include 'config.php';
	
    //open log file
    $log = "plus_bonus_log_" . date("Ymd") . ".txt";
    $fh = fopen($log, 'a') or die("Can't open log file");
	
	
    //connection string AND failure test
    try {
        $con = new PDO('mysql:host=localhost;dbname=test', 'root', 'magare1');
        $stringData = date("H:i") . "\tConnection established! \n";
        fwrite($fh, $stringData);
    } catch (PDOException $e) {
        $stringData = date("H:i") . "\tError!: " . $e->getMessage() . "\n";
        fwrite($fh, $stringData); 
        exit();
    }
	
    //query handler function
    function query_function ( $sql)
    {
        try
        {
            $rez = $GLOBALS['con']->query($sql);
            $stringData = date("H:i") . "\tQuery ( " . $sql . " ) completed seccessfully! \n";
            fwrite($GLOBALS['fh'], $stringData);
		
            return $rez;
        } catch (PDOException $e) 
        {
            $stringData = date("H:i") . "\tFailed to execute query ( " . $sql . " ): " . $e->getMessage() . " \n";
            fwrite($GLOBALS['fh'], $stringData);
            fclose($GLOBALS['fh']);
	
            exit();
        }
    }
	
	
	
    $sql = "create table bonus_program_income_" . date("Ymd") . " as select * from bonus_program_income_1";
    query_function ($sql);
	
    $sql = "select count(*) from bonus_program_income_" . date("Ymd");
    query_function ($sql);
	
    $sql = "select count(*) from bonus_program_income_1";
    query_function ($sql);
	
    $sql = "truncate table bonus_program_income_1";
    query_function ($sql);
	
    $sql = "insert into bonus_program_income_1 
			SELECT SUBSTRING(bonus_program_members.MSISDN,2) as msisdn, SUM(tele2_vouchers.amount) as months_1
			FROM  bonus_program_members
			LEFT JOIN tele2_vouchers 
			ON (tele2_vouchers.msisdn = SUBSTRING(bonus_program_members.MSISDN,2)
			AND date_format(tele2_vouchers.datum,'%Y.%m')=date_format(now() - interval 1 month,'%Y.%m'))
			GROUP BY bonus_program_members.MSISDN";
    query_function ($sql);
	
	
	//todo: provjeriti format amount i msisdn-a !
	$sql = "insert into adjustment.account_adjustment(process,status,msisdn,amount,balance_type,created,activated,validity,activate_account,free_text)
			select 104,1, substr(bonus_program_members.msisdn,5) ,bonus_program_definitions.reward ,100,now(),now(),0,0,'plusbonus'    
			from    bonus_program_members,tele2_cache,bonus_program_income_1,bonus_program_definitions
			where   bonus_program_members.PHONEBOOK = 1     
			AND tele2_cache.msisdn = SUBSTRING(bonus_program_members.msisdn,2)
			AND bonus_program_income_1.msisdn = SUBSTRING(bonus_program_members.msisdn,2)
				AND bonus_program_definitions.phonebook = bonus_program_members.phonebook
				AND bonus_program_definitions.income_min <= bonus_program_income_1.months_1
				AND bonus_program_definitions.income_max > bonus_program_income_1.months_1
				AND date_add(bonus_program_definitions.created_max, INTERVAL 1 MONTH) < date(tele2_cache.created)
				AND date_add(bonus_program_definitions.created_min, INTERVAL 1 MONTH) >= date(tele2_cache.created)
				AND bonus_program_definitions.reward > 0";
	query_function ($sql);
	
	$sql = "insert into adjustment.account_adjustment(process,status,msisdn,amount,balance_type,created,activated,validity,activate_account,free_text)
			select 104,1, substr(bonus_program_members.msisdn,5) ,bonus_program_definitions.reward ,100,now(),now(),0,0,'plusbonus'
			from    bonus_program_members,    tele2_cache,    bonus_program_income_1,    bonus_program_definitions
			where     bonus_program_members.PHONEBOOK = 0
				AND     tele2_cache.msisdn = SUBSTRING(bonus_program_members.msisdn,2)
				AND     bonus_program_income_1.msisdn = SUBSTRING(bonus_program_members.msisdn,2)
				AND     bonus_program_definitions.phonebook = bonus_program_members.phonebook
				AND     bonus_program_definitions.income_min <= bonus_program_income_1.months_1
				AND     bonus_program_definitions.income_max > bonus_program_income_1.months_1
				AND     date_add(bonus_program_definitions.created_max, INTERVAL 1 MONTH) < date(tele2_cache.created)
				AND     date_add(bonus_program_definitions.created_min, INTERVAL 1 MONTH) >= date(tele2_cache.created)
				AND     bonus_program_definitions.reward > 0";
	query_function ($sql);

	
    //close log file
    $stringData = "Script end. \n \n";
    fwrite($fh, $stringData);
    fclose($fh);
        
    unset($con);

?>