<?php

/**
 * This is the model class for table "df_role_service_access".
 *
 * The followings are the available columns in table 'df_role_service_access':
 * @property integer $id
 * @property integer $role_id
 * @property integer $service_id
 * @property string $service
 * @property string $component
 * @property boolean $read
 * @property boolean $create
 * @property boolean $update
 * @property boolean $delete
 */
class RoleServiceAccess extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return RoleServiceAccess the static model class
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
		return 'df_role_service_access';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('role_id, service, component', 'required'),
			array('role_id, service_id', 'numerical', 'integerOnly'=>true),
			array('service', 'length', 'max'=>40),
			array('component', 'length', 'max'=>80),
			array('read, create, update, delete', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, role_id, service_id, service, component, read, create, update, delete', 'safe', 'on'=>'search'),
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
			'role_id' => 'Role',
			'service_id' => 'Service',
			'service' => 'Service',
			'component' => 'Component',
			'read' => 'Read',
			'create' => 'Create',
			'update' => 'Update',
			'delete' => 'Delete',
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
		$criteria->compare('role_id',$this->role_id);
		$criteria->compare('service_id',$this->service_id);
		$criteria->compare('service',$this->service,true);
		$criteria->compare('component',$this->component,true);
		$criteria->compare('read',$this->read);
		$criteria->compare('create',$this->create);
		$criteria->compare('update',$this->update);
		$criteria->compare('delete',$this->delete);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}