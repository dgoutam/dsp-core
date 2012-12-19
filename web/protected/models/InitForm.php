<?php
use CloudServicesPlatform\Utilities\Utilities;
use CloudServicesPlatform\Storage\Database\PdoSqlDbSvc;

/**
 * InitForm class.
 * InitForm is the data structure for keeping system initialization data.
 * It is used by the 'init' action of 'SiteController'.
 */
class InitForm extends CFormModel
{
	public $username;
    public $password;
	public $email;
    public $firstName;
    public $lastName;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			// names, password, and email are required
			array('username, password, email, lastName, firstName', 'required'),
			// email has to be a valid email address
			array('email', 'email'),
		);
	}

	/**
	 * Declares customized attribute labels.
	 * If not declared here, an attribute would have a label that is
	 * the same as its name with the first letter in upper case.
	 */
	public function attributeLabels()
	{
		return array(
            'username'=>'Desired UserName',
            'password'=>'Desired Password',
            'firstName'=>'First Name',
            'lastName'=>'Last Name',
            'email'=>'Currently Valid Email',
		);
	}

    /**
     * Configures the system.
     * @throws Exception
     * @return boolean whether configuration is successful
     */
   	public function configure()
   	{
        try {
            $db = new PdoSqlDbSvc('df_');
            $contents = file_get_contents(Yii::app()->basePath.'/data/system_schema.json');
            if (empty($contents)) {
                throw new \Exception("Empty or no system schema file found.");
            }
            $contents = Utilities::jsonToArray($contents);
            // create system tables
            $tables = Utilities::getArrayValue('table', $contents);
            if (empty($tables)) {
                throw new \Exception("No default system schema found.");
            }
            $result = $db->createTables($tables, true, true);
            // setup session stored procedure
            $query = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
                      WHERE ROUTINE_TYPE="PROCEDURE"
                          AND ROUTINE_SCHEMA="dreamfactory"
                          AND ROUTINE_NAME="UpdateOrInsertSession";';
            $result = $db->singleSqlQuery($query);
            if ((empty($result)) || !isset($result[0]['ROUTINE_NAME'])) {
                switch ($db->getDriverType()) {
                case Utilities::DRV_SQLSRV:
                    $query =
                        'CREATE PROCEDURE dbo.UpdateOrInsertSession
                           @id nvarchar(32),
                           @start_time int,
                           @data nvarchar(4000)
                        AS
                        BEGIN
                            IF EXISTS (SELECT id FROM df_session WHERE id = @id)
                                BEGIN
                                    UPDATE df_session
                                    SET  data = @data, start_time = @start_time
                                    WHERE id = @id
                                END
                            ELSE
                                BEGIN
                                    INSERT INTO df_session (id, start_time, data)
                                    VALUES ( @id, @start_time, @data )
                                END
                        END';
                    break;
                case Utilities::DRV_MYSQL:
                default:
                    $query =
                        'CREATE PROCEDURE `UpdateOrInsertSession`(IN the_id nvarchar(32),
                                                                  IN the_start int,
                                                                  IN the_data nvarchar(4000))
                        BEGIN
                            IF EXISTS (SELECT `id` FROM `df_session` WHERE `id` = the_id) THEN
                                UPDATE df_session
                                SET  `data` = the_data, `start_time` = the_start
                                WHERE `id` = the_id;
                            ELSE
                                INSERT INTO df_session (`id`, `start_time`, `data`)
                                VALUES ( the_id, the_start, the_data );
                            END IF;
                        END';
                    break;
                }
                $db->singleSqlExecute($query);
            }
            //
            Yii::app()->db->schema->refresh();
            // init system tables with records
            $result = $db->retrieveSqlRecordsByFilter('df_service', 'id', '', 1);
            unset($result['total']);
            if (empty($result)) {
                $services = Utilities::getArrayValue('service', $contents);
                if (empty($services)) {
                    error_log(print_r($contents, true));
                    throw new \Exception("No default system services found.");
                }
                $db->createSqlRecords('df_service', $services, true);
            }
            $result = $db->retrieveSqlRecordsByFilter('df_app', 'id', '', 1);
            unset($result['total']);
            if (empty($result)) {
                $apps = Utilities::getArrayValue('app', $contents);
                if (empty($apps)) {
                    error_log(print_r($contents, true));
                    throw new \Exception("No default system apps found.");
                }
                $db->createSqlRecords('df_app', $apps, true);
            }

            // create and login first admin user
            // fill out the user fields for creation
            $fields = array('username' => $this->username,
                            'email' => $this->email,
                            'password' => md5($this->password),
                            'first_name' => $this->firstName,
                            'last_name' => $this->lastName,
                            'full_name' => $this->firstName . ' ' . $this->lastName,
                            'is_active' => true,
                            'is_sys_admin' => true,
                            'confirm_code' => 'y'
            );
            try {
                $result = $db->retrieveSqlRecordsByFilter('df_user', 'id', "username = '$this->username'", 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new \Exception("A user already exists with the username '$this->username'.");
                }
                $result = $db->createSqlRecord('df_user', $fields);
            }
            catch (\Exception $ex) {
                throw new \Exception("Failed to register new user!\n{$ex->getMessage()}");
            }
            return true;
        }
        catch (\Exception $ex) {
            throw $ex;
        }
        return false;
   	}
}