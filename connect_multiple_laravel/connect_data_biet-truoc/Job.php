<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{	
	const CREATED_AT = 'DATE_POST';
    const UPDATED_AT = 'DATE_UPDATE';
    //
    protected $connection  = "mysql2";
    protected $table = "jobs";
    protected $primaryKey = "ID";
    
    protected $fillable = ['TITLE','CONTENT','CD_ADDRESS','SALARY_FROM','SALARY_TO','EXPERIENCE','DATE_END', 'IS_DEL', 'ID_COMPANY', 'ID_USER_COMPANY','STATUS','SALARY_TYPE', 'AGE_FROM', 'AGE_TO', 'ID_JOB_TYPE', 'FULL_ADDRESS_WORK'];
}
