<?php

/**
 * This is the model class for table "app_group".
 *
 * The followings are the available columns in table 'app_group':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $createdBy
 * @property User $lastModifiedBy
 */
class AppGroup extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AppGroup the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'app_group';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, created_by_id, last_modified_by_id', 'required'),
			array('created_by_id, last_modified_by_id', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>80),
			array('description, created_date, last_modified_date', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'createdBy' => array(self::BELONGS_TO, 'User', 'created_by_id'),
			'lastModifiedBy' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'Id',
			'name' => 'Name',
			'description' => 'Description',
			'created_date' => 'Created Date',
			'last_modified_date' => 'Last Modified Date',
			'created_by_id' => 'Created By',
			'last_modified_by_id' => 'Last Modified By',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('last_modified_date',$this->last_modified_date,true);
		$criteria->compare('created_by_id',$this->created_by_id);
		$criteria->compare('last_modified_by_id',$this->last_modified_by_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}