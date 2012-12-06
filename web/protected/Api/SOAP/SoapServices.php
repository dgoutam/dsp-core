<?php
/*
    SOAP interface
    V0.1

    DreamFactory Software, Inc.
*/

require_once dirname(__FILE__) . '/../DreamFactory/AutoLoader.php';

class SoapServices
{
    protected $dfService;

    //-----Initialization -------
    public function __construct()
    {
        // connect to the service
        try {
            $this->dfService = new DreamFactory_Services();
        } catch (Exception $ex) {
            echo "Failed to start DreamFactory Services.\n{$ex->getMessage()}";
            exit;   // no need to go any further
        }
    }

    public function fieldsToAny($record)
    {
        if (isset($record['fields'])) {
            $record['fields'] = array('any' => DreamFactory_Utilities::simpleArrayToXml($record['fields']));
        }

        return $record;
    }

    public function resultsToFieldsAny($record)
    {
        return array('fields' => array('any' => DreamFactory_Utilities::simpleArrayToXml($record)));
    }

    public function AuthHeader($header)
    {
        try {
            // check for valid ticket
            if (isset($header->ticket)) {
                $_SESSION['public'] = $this->dfService->validateTicket($header->ticket);
            } else {
                // parse by hand for now
                error_log('parsing AuthHeader manually');
                $xml = DreamFactory_Utilities::getPostData();
                $ticket = ltrim(stristr(stristr($xml, '<ns1:ticket>'), '</ns1:ticket>', true), '<ns1:ticket>');
                if (!empty($ticket)) {
                    $_SESSION['public'] = $this->dfService->validateTicket($ticket);
                }
            }
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function login($params)
    {
        try {
            $username = $params->username;
            $password = $params->password;
            $appname = $params->appname;
            $result = $this->dfService->userLogin($username, $password, $appname);
            $result = DreamFactory_Utilities::array_key_lower($result);

            return $result;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function logOut()
    {
    }

    public function describeDatabase($params)
    {
        try {
            $result = $this->dfService->describeDatabase();

            return $result;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function describeTables($params)
    {
        try {
            $names = $params->table;
            $result = $this->dfService->describeTables($names);

            return $result;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function describeTable($params)
    {
        try {
            $name = $params->table;
            $result = $this->dfService->describeTable($name);

            return $result;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function createRecords($params)
    {
        try {
            $table = $params->table;
            $records = array();
            foreach ($params->records->record as $record) {
                $fieldset = $record->fields->any;
                $xml = simplexml_load_string("<fields>$fieldset</fields>");
                if (!$xml) {
                    $errstr = "[INVALIDREQUEST]: Invalid XML Data: ";
                    foreach (libxml_get_errors() as $error) {
                        $errstr .= $error->message . "\n";
                    }
                    throw new \Exception($errstr);
                }
                $fieldarray = DreamFactory_Utilities::xmlToArray($xml);
                $records[] = $fieldarray;
            }
            if (count($records) < 1) {
                throw new \Exception('No records in create request.');
            }
            $result = $this->dfService->createRecordsBatch($table, $records);
            $result = array_map(array($this, "fieldsToAny"), $result);
            $data = array('records' => array('record' => $result));

            return $data;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function updateRecords($params)
    {
        try {
            $table = $params->table;
            $records = array();
            foreach ($params->records->record as $record) {
                $fieldset = $record->fields->any;
                $xml = simplexml_load_string("<fields>$fieldset</fields>");
                if (!$xml) {
                    $errstr = "[INVALIDREQUEST]: Invalid XML Data: ";
                    foreach (libxml_get_errors() as $error) {
                        $errstr .= $error->message . "\n";
                    }
                    throw new \Exception($errstr);
                }
                $fieldarray = DreamFactory_Utilities::xmlToArray($xml);
                $records[] = $fieldarray;
            }
            if (count($records) < 1) {
                throw new \Exception('No records in create request.');
            }
            $result = $this->dfService->updateRecordsBatch($table, $records);
            $result = array_map(array($this, "fieldsToAny"), $result);
            $data = array('records' => array('record' => $result));

            return $data;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function deleteRecords($params)
    {
        try {
            $table = $params->table;
            $idlist = $params->ids;
            $result = $this->dfService->deleteRecordsByIds($table, $idlist);
            $result = array_map(array($this, "resultsToFieldsAny"), $result);
            $data = array('records' => array('record' => $result));

            return $data;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function filterRecords($params)
    {
        try {
            $table = $params->table;
            $fields = $params->fields;
            $filter = $params->filter;
            $limit = $params->limit;
            $result = $this->dfService->retrieveRecordsByFilter($table, $filter, $fields, $limit);
            $result = array_map(array($this, "resultsToFieldsAny"), $result);
            $data = array('records' => array('record' => $result));

            return $data;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }

    public function retrieveRecords($params)
    {
        try {
            $table = $params->table;
            $fields = $params->fields;
            $idlist = $params->ids;
            $result = $this->dfService->retrieveRecordsByIds($table, $idlist, $fields);
            $result = array_map(array($this, "resultsToFieldsAny"), $result);
            $data = array('records' => array('record' => $result));

            return $data;
        } catch (Exception $ex) {
            throw new SoapFault('Sender', $ex->getMessage());
        }
    }
}
