<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;

    public function authenticate()
    {
        $record = Drupal::authenticateUser($this->username, $this->password);
        if (false === $record) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }
        else {
            $this->_id = $record->drupal_id;
            $this->setState('email', $this->username);
            $this->setState('first_name', $record->first_name);
            $this->setState('last_name', $record->last_name);
            $this->setState('display_name', $record->display_name);
            $this->errorCode = self::ERROR_NONE;
        }
        /* not currently used
        $record = User::model()->findByAttributes(array('username'=>$this->username));
        if ($record === null)
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        else if (!CPasswordHelper::verifyPassword($this->password, $record->password))
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        else
        {
            $this->_id = $record->id;
            $this->setState('email', $record->email);
            $this->errorCode = self::ERROR_NONE;
        }
        */
        return !$this->errorCode;
    }

    public function getId()
    {
        return $this->_id;
    }
}