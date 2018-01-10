<?php
/**
 * Author: Robin Kaiser
 * E-Mail: m@r-k.mx
 *
 */

#namespace KaiserWerk\FaxTo;

class FaxTo
{

    /**
     * The fixed endpoint we will be sending requests to
     * @var mixed|string
     */
    protected $endpoint = 'https://fax.to/api/v1/%mode%?api_key=%apikey%';

    /**
     * Fax constructor.
     * @param string $apikey
     */
    public function __construct($apikey)
    {
        $this->endpoint = str_replace('%apikey%', $apikey, $this->endpoint);
    }

    /**
     * Gets the cash balance of the account.
     *
     * @return bool|array
     */
    public function getCashBalance()
    {
        $url = str_replace('%mode%', 'balance', $this->endpoint);
        $response = file_get_contents($url);
        if ($response === false) {
            return array('status' => 'error fetching balance');
        }
        $json = json_decode($response, true);
        if ($json['status'] === 'success') {
            return $json['balance'];
        }
        return $json;
    }

    /**
     * Gets the cost for sending a fax to the specified $fax_number with
     * the specified $ocument_id of an already uploaded file.
     *
     * @param $fax_number
     * @param int $document_id
     * @return array|float
     */
    public function getFaxCost($fax_number, $document_id)
    {
        if (empty($fax_number)) {
            return array('status' => 'missing required parameter fax_number');
        }
        if (empty($document_id)) {
            return array('status' => 'missing required parameter document_id');
        }
        $params = array(
            'fax_number' => $fax_number,
        );
        $response = file_get_contents(
            str_replace('%mode%', 'fax/' . $document_id . '/costs', $this->endpoint) .
            '&' . http_build_query($params));
        $json = json_decode($response, true);
        if ($json['status'] === 'success') {
            return $json['balance'];
        }
        return $json;
    }

    /**
     * Gets the status for the job_id of a fax.
     *
     * @param int $job_id
     * @return array
     */
    public function getFaxStatus($job_id)
    {
        if (empty($job_id)) {
            return array('status' => 'missing required parameter job_id');
        }

        $response = file_get_contents(str_replace('%mode%', 'fax/' . $job_id . '/status', $this->endpoint));
        $json = json_decode($response, true);
        return $json;
    }

    /**
     * Gets the complete fax history for the account. Can be
     * limited to $limit entries. If $limit is set, $page can
     * be used to return a certain page to create a pagination.
     *
     * @param null|int $limit
     * @param null|int $page
     * @return array
     */
    public function getFaxHistory($limit = null, $page = null)
    {
        $params = array();
        if (isset($limit)) {
            $params['limit'] = $limit;
        }
        if (isset($page)) {
            $params['page'] = $page;
        }
        $url = str_replace('%mode%', 'fax-history', $this->endpoint);
        if (count($params) > 0) {
            $url .= '&' . http_build_query($params);
        }
        $response = file_get_contents($url);
        if ($response === false) {
            return array('status' => 'error fetching history data from ' . $url);
        }
        $json = json_decode($response, true);
        return $json;
    }

    /**
     * Sends out a fax.
     *
     * @param $fax_number
     * @param null|int $document_id
     * @param null|string $tsi_number
     * @param null|string $file
     * @param null|int $delete_file
     * @return array
     */
    public function sendFax($fax_number, $document_id = null, $tsi_number = null, $file = null, $delete_file = null)
    {
        if (empty($fax_number)) {
            return array('status' => 'missing fax number');
        }
        if (empty($document_id) && empty($file)) {
            return array('status' => 'missing document source');
        }

        $postfields = array(
            'fax_number' => $fax_number,
            'document_id' => $document_id,
        );

        $options = array(
            CURLOPT_URL => str_replace('%mode%', 'fax', $this->endpoint),
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postfields,
        );

        if (!empty($file)) {
            $document_id = null;

            $mime = mime_content_type($file);
            $cfile = new \CURLFile(realpath($file),$mime, $file);

            unset($postfields['document_id']);
            $postfields['file'] = $cfile;

            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = $postfields;
            unset($options[CURLOPT_POST]);

        }

        #return $options;

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if (!empty($err)) {
            return array('status' => 'cURL error: ' . $err);
        }
        $json = json_decode($response, true);
        return $json;
        /*
         * If status = executed, we have to wait for the callback (see below)
         */

        /*
         * Example failed callback response (POST)
         *  Array
            (
                [fax_job_id] => 223561
                [status] => failed
                [msg_code] => no_answer
                [message] => Fax Failed - No Answer from Fax machine
            )
         * If it was successful, then status = success
         */
    }

    /**
     * Gets a list of all documents uploaded.
     *
     * @return array
     */
    public function getFiles()
    {
        $defaults = [
            CURLOPT_URL => str_replace('%mode%', 'files', $this->endpoint),
            CURLOPT_RETURNTRANSFER => true,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $defaults);
        $response = curl_exec($ch);

        $json = json_decode($response, true);
        return $json;

    }

    /**
     * Uploads a file (document) to the account.
     *
     * @param string $file
     * @param bool $is_remote
     * @return array
     */
    public function uploadFile($file, $is_remote = false)
    {
        if (empty($file)) {
            return array('status' => 'parameter file is missing');
        }
        if (!file_exists($file)) {
            return array('status' => 'file not found');
        }
        if (!is_readable($file)) {
            return array('status' => 'file not readable');
        }
        $mime = mime_content_type($file);
        $cfile = new \CURLFile(realpath($file), $mime, $file);

        $curl = curl_init();

        $options = array(
            CURLOPT_URL => str_replace('%mode%', 'files', $this->endpoint),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file' => $cfile),
            #CURLOPT_SSL_VERIFYPEER => false,
        );

        if ($is_remote === true) {
            $options[CURLOPT_POSTFIELDS] = array('AddRemoteFile' => $cfile);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        $err = curl_error($curl);
        if (!empty($err)) {
            return array('status' => 'cURL error: ' . $err);
        }
        curl_close($curl);
        $decodedResponse = json_decode($response, true);
        return $decodedResponse;
    }

    /**
     * Delete a file (document).
     *
     * @param int $file_id
     * @return array
     */
    public function deleteFile($file_id)
    {
        if (empty($file_id)) {
            return array('status' => 'missing parameter file_id');
        }

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => str_replace('%mode%', 'files/' . $file_id, $this->endpoint),
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        // Decode the json object you retrieved when you ran the request.
        $decodedResponse = json_decode($response, true);
        return $decodedResponse;
    }
}