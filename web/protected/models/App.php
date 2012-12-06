<?php

/**
 * This is the model class for table "df_app".
 *
 * The followings are the available columns in table 'df_app':
 * @property integer $id
 * @property string $name
 * @property string $label
 * @property string $description
 * @property boolean $is_active
 * @property string $url
 * @property boolean $is_url_external
 * @property string $app_group_ids
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 */
class App extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return App the static model class
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
		return 'df_app';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, label, created_by_id, last_modified_by_id', 'required'),
			array('created_by_id, last_modified_by_id', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>40),
			array('label', 'length', 'max'=>80),
			array('description, is_active, url, is_url_external, app_group_ids, created_date, last_modified_date', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, label, description, is_active, url, is_url_external, app_group_ids, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'label' => 'Label',
			'description' => 'Description',
			'is_active' => 'Is Active',
			'url' => 'Url',
			'is_url_external' => 'Is Url External',
			'app_group_ids' => 'App Group Ids',
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
		$criteria->compare('label',$this->label,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('is_active',$this->is_active);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('is_url_external',$this->is_url_external);
		$criteria->compare('app_group_ids',$this->app_group_ids,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('last_modified_date',$this->last_modified_date,true);
		$criteria->compare('created_by_id',$this->created_by_id);
		$criteria->compare('last_modified_by_id',$this->last_modified_by_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}